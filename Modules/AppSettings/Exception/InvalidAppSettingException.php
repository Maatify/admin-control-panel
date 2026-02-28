<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

use Maatify\AppSettings\Domain\Enum\AppSettingsErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

/**
 * Thrown when a setting group or key violates
 * whitelist or normalization rules.
 */
final class InvalidAppSettingException extends AppSettingInvalidArgumentException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return AppSettingsErrorCodeEnum::INVALID_APP_SETTING;
    }
}
