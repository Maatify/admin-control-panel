<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Contracts;

use Throwable;

interface ApiAwareExceptionInterface extends Throwable
{
    public function getHttpStatus(): int;

    public function getErrorCode(): ErrorCodeInterface;

    public function getCategory(): ErrorCategoryInterface;

    public function isSafe(): bool;

    /**
     * @return array<string, mixed>
     */
    public function getMeta(): array;

    public function isRetryable(): bool;
}
