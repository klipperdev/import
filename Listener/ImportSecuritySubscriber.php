<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import\Listener;

use Klipper\Component\Import\Event\PreImportEvent;
use Klipper\Component\Import\Exception\InvalidArgumentException;
use Klipper\Component\Security\Model\UserInterface;
use Klipper\Component\Security\Organizational\OrganizationalContext;
use Klipper\Component\Security\Token\ConsoleToken;
use Klipper\Component\SecurityExtra\Helper\OrganizationalContextHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ImportSecuritySubscriber implements EventSubscriberInterface
{
    private EventDispatcherInterface $dispatcher;

    private TokenStorageInterface $tokenStorage;

    private ?OrganizationalContext $orgContext;

    private ?OrganizationalContextHelper $orgContextHelper;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        TokenStorageInterface $tokenStorage,
        ?OrganizationalContext $orgContext,
        ?OrganizationalContextHelper $orgContextHelper
    ) {
        $this->dispatcher = $dispatcher;
        $this->tokenStorage = $tokenStorage;
        $this->orgContext = $orgContext;
        $this->orgContextHelper = $orgContextHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreImportEvent::class => ['onPreImport', 0],
        ];
    }

    public function onPreImport(PreImportEvent $event): void
    {
        $import = $event->getImport();
        $user = $import->getCreatedBy();
        $org = $import->getOrganization();

        if (null === $user && null === $org) {
            return;
        }

        if (null === $user && null !== $org) {
            throw new InvalidArgumentException(
                'The user is required on import if the organization is defined'
            );
        }

        if (null !== $user) {
            $this->authUser($user);
        }

        if (null !== $this->orgContext && null !== $this->orgContextHelper) {
            if (null === $org) {
                $this->orgContext->setCurrentOrganization(false);
            } elseif ($org->isUserOrganization()) {
                $this->orgContext->setCurrentOrganization(null);
            } else {
                $this->authOrganization($org->getName());
            }
        }
    }

    private function authUser(UserInterface $user): void
    {
        $token = new ConsoleToken('importer', $user, $user->getRoles());

        $this->tokenStorage->setToken($token);
        $this->dispatcher->dispatch(
            new AuthenticationSuccessEvent($token),
            AuthenticationEvents::AUTHENTICATION_SUCCESS
        );
    }

    private function authOrganization(string $organizationName): void
    {
        $this->orgContextHelper->setCurrentOrganizationUser($organizationName);

        if (!$this->orgContext->isOrganization()) {
            throw new InvalidArgumentException(sprintf(
                'The organization with the name "%s" does not exist',
                $organizationName
            ));
        }
    }
}
