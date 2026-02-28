<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

use Maatify\AppSettings\Domain\Enum\AppSettingsErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

/**
 * Thrown when a requested setting does not exist
 * or is inactive.
 */
final class AppSettingNotFoundException extends AppSettingsNotFoundException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return AppSettingsErrorCodeEnum::APP_SETTING_NOT_FOUND;
    }
}
