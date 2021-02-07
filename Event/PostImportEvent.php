<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import\Event;

use Klipper\Component\Import\Model\ImportInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class PostImportEvent extends AbstractImportEvent
{
    private bool $hasErrors;

    public function __construct(ImportInterface $import, bool $hasErrors)
    {
        parent::__construct($import);

        $this->hasErrors = $hasErrors;
    }

    public function hasErrors(): bool
    {
        return $this->hasErrors;
    }
}
