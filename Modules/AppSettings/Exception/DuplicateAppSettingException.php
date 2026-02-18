<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

/**
 * Class: DuplicateAppSettingException
 *
 * Thrown when trying to create a setting that already exists.
 */
use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

final class DuplicateAppSettingException
    extends GenericConflictMaatifyException
{
}
