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

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
abstract class ImportErrorCodes
{
    public const ERROR_COPY_FILE = 'error_copy_file';

    public const UNREADABLE_FILE = 'unreadable_file';

    public const UNDEFINED_ADAPTER = 'undefined_adapter';

    public const NO_ADAPTER_AVAILABLE = 'no_adapter_available';

    public const UNEXPECTED_ERROR = 'unexpected_error';
}
