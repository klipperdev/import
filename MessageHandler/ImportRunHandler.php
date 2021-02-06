<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import\MessageHandler;

use Klipper\Component\Import\Exception\ImportNotFoundException;
use Klipper\Component\Import\ImportManagerInterface;
use Klipper\Component\Import\Message\ImportRunMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ImportRunHandler implements MessageHandlerInterface
{
    private ImportManagerInterface $importManager;

    public function __construct(ImportManagerInterface $importManager)
    {
        $this->importManager = $importManager;
    }

    public function __invoke(ImportRunMessage $message): void
    {
        try {
            $this->importManager->run($message->getId());
        } catch (ImportNotFoundException $e) {
            // Skip not found import exception
        }
    }
}
