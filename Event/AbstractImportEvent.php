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
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
abstract class AbstractImportEvent extends Event
{
    protected ImportInterface $import;

    public function __construct(ImportInterface $import)
    {
        $this->import = $import;
    }

    public function getImport(): ImportInterface
    {
        return $this->import;
    }
}
