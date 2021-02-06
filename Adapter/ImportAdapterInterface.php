<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import\Adapter;

use Klipper\Component\Import\ImportConfigInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface ImportAdapterInterface
{
    public function import(ImportConfigInterface $config): bool;

    public function validate(ImportConfigInterface $config): bool;
}
