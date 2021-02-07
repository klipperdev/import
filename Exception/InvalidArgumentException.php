<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\Import\Exception;

/**
 * Base InvalidArgumentException for the import.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}