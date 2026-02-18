<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

class InvalidIdentifierStateException extends MaatifyException
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::BUSINESS_RULE_VIOLATION;
    }

    protected function defaultCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::BUSINESS_RULE;
    }

    protected function defaultHttpStatus(): int
    {
        return 409;
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
