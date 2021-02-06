<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import;

use Klipper\Component\Import\Model\ImportInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface ImportManagerInterface
{
    public function reset(ImportInterface $import): bool;

    /**
     * @param ImportInterface|int|string $import The import instance or the import id
     */
    public function run($import): ImportInterface;
}
