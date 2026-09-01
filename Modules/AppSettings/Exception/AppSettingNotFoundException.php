<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;

/**
 * Class: AppSettingNotFoundException
 *
 * Thrown when a requested setting does not exist
 * or is inactive.
 */
final class AppSettingNotFoundException extends AppSettingException
{
    protected function defaultCategory(): ErrorCategoryInterface
    {
        return ErrorCategoryEnum::NOT_FOUND;
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::RESOURCE_NOT_FOUND;
    }

    protected function defaultHttpStatus(): int
    {
        return 404;
    }
}
