<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception;

use Maatify\Exceptions\Contracts\ApiAwareExceptionInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use RuntimeException;
use Throwable;

abstract class MaatifyException extends RuntimeException implements ApiAwareExceptionInterface
{
    /** @var array<string, mixed> */
    private array $meta = [];

    private ?ErrorCodeEnum $errorCodeOverride = null;
    private ?ErrorCategoryEnum $categoryOverride = null;
    private ?int $httpStatusOverride = null;
    private ?bool $isSafeOverride = null;
    private ?bool $isRetryableOverride = null;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ErrorCodeEnum $errorCodeOverride = null,
        ?ErrorCategoryEnum $categoryOverride = null,
        ?int $httpStatusOverride = null,
        ?bool $isSafeOverride = null,
        ?bool $isRetryableOverride = null,
        array $meta = [],
    ) {
        parent::__construct($message, $code, $previous);

        $this->errorCodeOverride = $errorCodeOverride;
        $this->categoryOverride = $categoryOverride;
        $this->httpStatusOverride = $httpStatusOverride;
        $this->isSafeOverride = $isSafeOverride;
        $this->isRetryableOverride = $isRetryableOverride;
        $this->meta = $meta;
    }

    // ---- Defaults (Families override these) ----

    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::MAATIFY_ERROR;
    }

    protected function defaultCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::SYSTEM;
    }

    protected function defaultHttpStatus(): int
    {
        return 500;
    }

    protected function defaultIsSafe(): bool
    {
        return false;
    }

    protected function defaultIsRetryable(): bool
    {
        return false;
    }

    // ---- Final getters (contract) ----

    final public function getErrorCode(): ErrorCodeEnum
    {
        return $this->errorCodeOverride ?? $this->defaultErrorCode();
    }

    final public function getCategory(): ErrorCategoryEnum
    {
        return $this->categoryOverride ?? $this->defaultCategory();
    }

    final public function getHttpStatus(): int
    {
        return $this->httpStatusOverride ?? $this->defaultHttpStatus();
    }

    final public function isSafe(): bool
    {
        return $this->isSafeOverride ?? $this->defaultIsSafe();
    }

    final public function isRetryable(): bool
    {
        return $this->isRetryableOverride ?? $this->defaultIsRetryable();
    }

    final public function getMeta(): array
    {
        return $this->meta;
    }
}
