<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;

/**
 * Class: AppSettingProtectedException
 *
 * Thrown when attempting to modify or deactivate
 * a protected application setting.
 */
final class AppSettingProtectedException extends AppSettingException
{
    protected function defaultCategory(): ErrorCategoryInterface
    {
        return ErrorCategoryEnum::BUSINESS_RULE;
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::BUSINESS_RULE_VIOLATION;
    }

    protected function defaultHttpStatus(): int
    {
        return 422;
    }
}
