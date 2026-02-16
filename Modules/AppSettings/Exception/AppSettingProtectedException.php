<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

/**
 * Class: AppSettingProtectedException
 *
 * Thrown when attempting to modify or deactivate
 * a protected application setting.
 */
final class AppSettingProtectedException extends AppSettingException
{
}
