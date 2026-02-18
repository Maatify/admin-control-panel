<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

class InvalidSessionException extends MaatifyException
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::INVALID_SESSION;
    }

    protected function defaultCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::AUTHENTICATION;
    }

    protected function defaultHttpStatus(): int
    {
        return 401;
    }

    protected function defaultIsSafe(): bool
    {
        return true;
    }

    protected function defaultIsRetryable(): bool
    {
        return false;
    }
}
