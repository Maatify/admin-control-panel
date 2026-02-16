<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Contracts;

use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Throwable;

interface ApiAwareExceptionInterface extends Throwable
{
    public function getHttpStatus(): int;

    public function getErrorCode(): ErrorCodeEnum;

    public function getCategory(): ErrorCategoryEnum;

    public function isSafe(): bool;

    /**
     * @return array<string, mixed>
     */
    public function getMeta(): array;

    public function isRetryable(): bool;
}
