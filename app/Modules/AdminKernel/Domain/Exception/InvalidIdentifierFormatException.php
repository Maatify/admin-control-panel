<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

class InvalidIdentifierFormatException extends MaatifyException
{
    public function __construct(string $message = "")
    {
        parent::__construct($message);
    }

    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::INVALID_ARGUMENT;
    }

    protected function defaultCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::VALIDATION;
    }

    protected function defaultHttpStatus(): int
    {
        return 400;
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
