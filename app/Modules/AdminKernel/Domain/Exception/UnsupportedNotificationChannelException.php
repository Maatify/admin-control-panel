<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

class UnsupportedNotificationChannelException extends MaatifyException
{
    public function __construct(string $channel)
    {
        parent::__construct(
            message: sprintf('No sender supports the channel: %s', $channel),
            meta: ['channel' => $channel]
        );
    }

    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::UNSUPPORTED_OPERATION;
    }

    protected function defaultCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::UNSUPPORTED;
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
