<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception;

use LogicException;
use Maatify\Exceptions\Contracts\ApiAwareExceptionInterface;
use Maatify\Exceptions\Enum\ErrorCategoryEnum;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use RuntimeException;
use Throwable;

abstract class MaatifyException extends RuntimeException implements ApiAwareExceptionInterface
{
    private const SEVERITY_RANKING = [
        ErrorCategoryEnum::SYSTEM->value => 90,
        ErrorCategoryEnum::RATE_LIMIT->value => 80,
        ErrorCategoryEnum::AUTHENTICATION->value => 70,
        ErrorCategoryEnum::AUTHORIZATION->value => 60,
        ErrorCategoryEnum::VALIDATION->value => 50,
        ErrorCategoryEnum::BUSINESS_RULE->value => 40,
        ErrorCategoryEnum::CONFLICT->value => 30,
        ErrorCategoryEnum::NOT_FOUND->value => 20,
        ErrorCategoryEnum::UNSUPPORTED->value => 10,
    ];

    private const ALLOWED_ERROR_CODES = [
        ErrorCategoryEnum::VALIDATION->value => [ErrorCodeEnum::INVALID_ARGUMENT, ErrorCodeEnum::VALIDATION_FAILED],
        ErrorCategoryEnum::AUTHENTICATION->value => [ErrorCodeEnum::UNAUTHORIZED, ErrorCodeEnum::SESSION_EXPIRED],
        ErrorCategoryEnum::AUTHORIZATION->value => [ErrorCodeEnum::FORBIDDEN],
        ErrorCategoryEnum::CONFLICT->value => [ErrorCodeEnum::CONFLICT],
        ErrorCategoryEnum::NOT_FOUND->value => [ErrorCodeEnum::RESOURCE_NOT_FOUND],
        ErrorCategoryEnum::BUSINESS_RULE->value => [ErrorCodeEnum::BUSINESS_RULE_VIOLATION],
        ErrorCategoryEnum::UNSUPPORTED->value => [ErrorCodeEnum::UNSUPPORTED_OPERATION],
        ErrorCategoryEnum::SYSTEM->value => [ErrorCodeEnum::MAATIFY_ERROR, ErrorCodeEnum::DATABASE_CONNECTION_FAILED],
        ErrorCategoryEnum::RATE_LIMIT->value => [ErrorCodeEnum::TOO_MANY_REQUESTS],
    ];

    /** @var array<string, mixed> */
    private array $meta = [];

    private ?ErrorCodeEnum $errorCodeOverride = null;
    private ?int $httpStatusOverride = null;
    private ?bool $isSafeOverride = null;
    private ?bool $isRetryableOverride = null;

    private ?ErrorCategoryEnum $escalatedCategory = null;
    private ?int $escalatedHttpStatus = null;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ErrorCodeEnum $errorCodeOverride = null,
        ?int $httpStatusOverride = null,
        ?bool $isSafeOverride = null,
        ?bool $isRetryableOverride = null,
        array $meta = [],
    ) {
        parent::__construct($message, $code, $previous);

        $this->errorCodeOverride = $errorCodeOverride;
        $this->httpStatusOverride = $httpStatusOverride;
        $this->isSafeOverride = $isSafeOverride;
        $this->isRetryableOverride = $isRetryableOverride;
        $this->meta = $meta;

        $this->validateErrorCodeOverride();
        $this->validateHttpStatusOverride();
        $this->calculateEscalation($previous);
    }

    private function validateErrorCodeOverride(): void
    {
        if ($this->errorCodeOverride === null) {
            return;
        }

        $allowed = self::ALLOWED_ERROR_CODES[$this->defaultCategory()->value];

        if (!in_array($this->errorCodeOverride, $allowed, true)) {
            throw new LogicException(sprintf(
                'ErrorCode %s is not allowed for category %s',
                $this->errorCodeOverride->value,
                $this->defaultCategory()->value
            ));
        }
    }

    private function validateHttpStatusOverride(): void
    {
        if ($this->httpStatusOverride === null) {
            return;
        }

        $default = $this->defaultHttpStatus();

        if (intdiv($this->httpStatusOverride, 100) !== intdiv($default, 100)) {
            throw new LogicException(sprintf(
                'HttpStatus override %d must belong to the same class family as default %d',
                $this->httpStatusOverride,
                $default
            ));
        }
    }

    private function calculateEscalation(?Throwable $previous): void
    {
        if (!($previous instanceof ApiAwareExceptionInterface)) {
            return;
        }

        // Category Escalation
        $currentCategory = $this->defaultCategory();
        $previousCategory = $previous->getCategory();

        $currentSeverity = self::SEVERITY_RANKING[$currentCategory->value];
        $previousSeverity = self::SEVERITY_RANKING[$previousCategory->value];

        if ($previousSeverity > $currentSeverity) {
            $this->escalatedCategory = $previousCategory;
        }

        // HttpStatus Escalation
        $currentStatus = $this->httpStatusOverride ?? $this->defaultHttpStatus();
        $previousStatus = $previous->getHttpStatus();

        if ($previousStatus > $currentStatus) {
            $this->escalatedHttpStatus = $previousStatus;
        }
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
        return $this->escalatedCategory ?? $this->defaultCategory();
    }

    final public function getHttpStatus(): int
    {
        return $this->escalatedHttpStatus ?? $this->httpStatusOverride ?? $this->defaultHttpStatus();
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
