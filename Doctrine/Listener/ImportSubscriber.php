<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import\Doctrine\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Klipper\Component\DoctrineExtensionsExtra\Util\ListenerUtil;
use Klipper\Component\Import\Model\ImportInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Doctrine subscriber for the import model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ImportSubscriber implements EventSubscriber
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::onFlush,
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->preUpdate($event);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();

        if ($object instanceof ImportInterface) {
            if (null === $object->getLocale()) {
                $object->setLocale(\Locale::getDefault());
            }
        }
    }

    /**
     * On flush action.
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof ImportInterface) {
                if ('in_progress' === $entity->getStatus()) {
                    ListenerUtil::thrownError($this->translator->trans(
                        'klipper_import.orm_listener.import_in_progress',
                        [],
                        'validators'
                    ));
                }
            }
        }
    }
}
