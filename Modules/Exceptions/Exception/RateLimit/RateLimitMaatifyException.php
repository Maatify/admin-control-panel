<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\RateLimit;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Exception\MaatifyException;

abstract class RateLimitMaatifyException extends MaatifyException
{
    protected function defaultCategory(): ErrorCategoryEnum { return ErrorCategoryEnum::RATE_LIMIT; }
    protected function defaultHttpStatus(): int { return 429; }
    protected function defaultIsSafe(): bool { return true; }
    protected function defaultIsRetryable(): bool { return true; }
}
