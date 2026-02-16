<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

/**
 * Class: DuplicateAppSettingException
 *
 * Thrown when trying to create a setting that already exists.
 */
final class DuplicateAppSettingException extends AppSettingException
{
}
