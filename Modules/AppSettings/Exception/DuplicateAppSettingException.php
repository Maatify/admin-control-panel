<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

use Maatify\AppSettings\Domain\Enum\AppSettingsErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;

/**
 * Thrown when trying to create a setting that already exists.
 */
final class DuplicateAppSettingException extends AppSettingsConflictException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return AppSettingsErrorCodeEnum::DUPLICATE_APP_SETTING;
    }
}
