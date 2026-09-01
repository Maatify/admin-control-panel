<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;

/**
 * Class: DuplicateAppSettingException
 *
 * Thrown when trying to create a setting that already exists.
 */
final class DuplicateAppSettingException extends AppSettingException
{
    protected function defaultCategory(): ErrorCategoryInterface
    {
        return ErrorCategoryEnum::CONFLICT;
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::CONFLICT;
    }

    protected function defaultHttpStatus(): int
    {
        return 409;
    }
}
