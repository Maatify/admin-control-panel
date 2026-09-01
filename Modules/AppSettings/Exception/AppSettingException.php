<?php

declare(strict_types=1);

namespace Maatify\AppSettings\Exception;

use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class AppSettingException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryInterface
    {
        return ErrorCategoryEnum::SYSTEM;
    }

    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::MAATIFY_ERROR;
    }

    protected function defaultHttpStatus(): int
    {
        return 500;
    }
}
