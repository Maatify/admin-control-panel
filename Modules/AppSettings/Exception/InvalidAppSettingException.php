<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

/**
 * Class: InvalidAppSettingException
 *
 * Thrown when a setting group or key violates
 * whitelist or normalization rules.
 */
final class InvalidAppSettingException extends AppSettingInvalidArgumentException
{
}
