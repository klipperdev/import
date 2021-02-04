<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import\Validator\Constraints;

use Klipper\Component\Choice\Validator\Constraints\Choice;
use Klipper\Component\Import\Choice\ImportStatus;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Annotation
 */
final class ImportStatusChoice extends Choice
{
    public $callback = [
        ImportStatus::class,
        'getValues',
    ];
}
