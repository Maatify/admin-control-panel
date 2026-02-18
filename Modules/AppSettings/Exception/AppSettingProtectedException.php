<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

use Maatify\AppSettings\Domain\Enum\AppSettingsErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

/**
 * Thrown when attempting to modify or deactivate
 * a protected application setting.
 */
final class AppSettingProtectedException extends AppSettingException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return AppSettingsErrorCodeEnum::APP_SETTING_PROTECTED;
    }
}
