<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/**
 * Thrown when a requested setting does not exist
 * or is inactive.
 */
final class AppSettingNotFoundException
    extends ResourceNotFoundMaatifyException
{
}
