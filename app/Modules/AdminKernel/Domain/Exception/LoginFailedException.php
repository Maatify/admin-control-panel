<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\Exceptions\Constants\ErrorCategoryEnum;
use Maatify\Exceptions\Constants\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

class LoginFailedException extends MaatifyException
{
    public function __construct(string $message = 'Invalid credentials')
    {
        parent::__construct($message, 401);
    }

    public function getErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::INVALID_CREDENTIALS; // Assuming this exists or falls back
    }

    public function getCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::AUTHENTICATION;
    }

    public function isSafe(): bool
    {
        return true;
    }
}
