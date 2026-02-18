<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

class AuthStateException extends MaatifyException
{
    public const REASON_NOT_VERIFIED = 'not_verified';
    public const REASON_SUSPENDED = 'suspended';
    public const REASON_DISABLED = 'disabled';

    public function __construct(
        private readonly string $reason,
        string $message
    ) {
        parent::__construct(message: $message, meta: ['reason' => $reason]);
    }

    public function reason(): string
    {
        return $this->reason;
    }

    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::AUTH_STATE_VIOLATION;
    }

    protected function defaultCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::AUTHENTICATION;
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
