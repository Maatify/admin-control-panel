<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception;

use LogicException;
use Maatify\Exceptions\Contracts\ApiAwareExceptionInterface;
use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Contracts\ErrorPolicyInterface;
use Maatify\Exceptions\Contracts\EscalationPolicyInterface;
use Maatify\Exceptions\Policy\DefaultErrorPolicy;
use Maatify\Exceptions\Policy\DefaultEscalationPolicy;
use RuntimeException;
use Throwable;

/**
 * Base exception for all Maatify exceptions.
 *
 * - Policy-driven validation
 * - Escalation fully injectable
 * - No enum coupling
 * - Extensible & reusable
 * - Supports global default policy overrides
 */
abstract class MaatifyException extends RuntimeException implements ApiAwareExceptionInterface
{
    /** @var array<string, mixed> */
    private array $meta = [];

    private ErrorPolicyInterface $policy;
    private EscalationPolicyInterface $escalationPolicy;

    private ?ErrorCodeInterface $errorCodeOverride = null;
    private ?int $httpStatusOverride = null;
    private ?bool $isSafeOverride = null;
    private ?bool $isRetryableOverride = null;

    private ?ErrorCategoryInterface $escalatedCategory = null;
    private ?int $escalatedHttpStatus = null;

    // ---------------------------------------------------------------------
    // Global Policy Overrides (Optional)
    // ---------------------------------------------------------------------

    private static ?ErrorPolicyInterface $globalPolicy = null;
    private static ?EscalationPolicyInterface $globalEscalationPolicy = null;

    /**
     * Override default policy globally for all new exceptions.
     */
    public static function setGlobalPolicy(
        ErrorPolicyInterface $policy
    ): void {
        self::$globalPolicy = $policy;
    }

    /**
     * Override default escalation policy globally.
     */
    public static function setGlobalEscalationPolicy(
        EscalationPolicyInterface $policy
    ): void {
        self::$globalEscalationPolicy = $policy;
    }

    /**
     * Reset global policies to defaults.
     */
    public static function resetGlobalPolicies(): void
    {
        self::$globalPolicy = null;
        self::$globalEscalationPolicy = null;
    }

    /**
     * @param array<string, mixed> $meta
     *
     * WARNING:
     * Global policies are process-wide.
     * In long-running environments (Swoole, RoadRunner),
     * ensure policies are set during bootstrap only.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ErrorCodeInterface $errorCodeOverride = null,
        ?int $httpStatusOverride = null,
        ?bool $isSafeOverride = null,
        ?bool $isRetryableOverride = null,
        array $meta = [],
        ?ErrorPolicyInterface $policy = null,
        ?EscalationPolicyInterface $escalationPolicy = null,
    ) {
        parent::__construct($message, $code, $previous);

        $this->policy = $policy
                        ?? self::$globalPolicy
                           ?? DefaultErrorPolicy::default();

        $this->escalationPolicy = $escalationPolicy
                                  ?? self::$globalEscalationPolicy
                                     ?? DefaultEscalationPolicy::default();

        $this->errorCodeOverride = $errorCodeOverride;
        $this->httpStatusOverride = $httpStatusOverride;
        $this->isSafeOverride = $isSafeOverride;
        $this->isRetryableOverride = $isRetryableOverride;
        $this->meta = $meta;

        $this->validateErrorCodeOverride();
        $this->validateHttpStatusOverride();
        $this->calculateEscalation($previous);
    }

    // ---------------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------------

    private function validateErrorCodeOverride(): void
    {
        if ($this->errorCodeOverride === null) {
            return;
        }

        $this->policy->validate(
            $this->errorCodeOverride,
            $this->defaultCategory()
        );
    }

    private function validateHttpStatusOverride(): void
    {
        if ($this->httpStatusOverride === null) {
            return;
        }

        $default = $this->defaultHttpStatus();

        if (intdiv($this->httpStatusOverride, 100) !== intdiv($default, 100)) {
            throw new LogicException(sprintf(
                'HttpStatus override %d must belong to same class family as default %d',
                $this->httpStatusOverride,
                $default
            ));
        }
    }

    // ---------------------------------------------------------------------
    // Escalation
    // ---------------------------------------------------------------------

    private function calculateEscalation(?Throwable $previous): void
    {
        if (!($previous instanceof ApiAwareExceptionInterface)) {
            return;
        }

        $currentCategory = $this->defaultCategory();
        $previousCategory = $previous->getCategory();

        $this->escalatedCategory =
            $this->escalationPolicy->escalateCategory(
                $currentCategory,
                $previousCategory,
                $this->policy
            );

        $currentStatus = $this->httpStatusOverride ?? $this->defaultHttpStatus();
        $previousStatus = $previous->getHttpStatus();

        $this->escalatedHttpStatus =
            $this->escalationPolicy->escalateHttpStatus(
                $currentStatus,
                $previousStatus
            );
    }

    // ---------------------------------------------------------------------
    // Default behavior (subclasses MUST define core identity)
    // ---------------------------------------------------------------------

    abstract protected function defaultErrorCode(): ErrorCodeInterface;

    abstract protected function defaultCategory(): ErrorCategoryInterface;

    abstract protected function defaultHttpStatus(): int;

    protected function defaultIsSafe(): bool
    {
        return false;
    }

    protected function defaultIsRetryable(): bool
    {
        return false;
    }

    // ---------------------------------------------------------------------
    // Final API Contract Methods
    // ---------------------------------------------------------------------

    final public function getErrorCode(): ErrorCodeInterface
    {
        return $this->errorCodeOverride ?? $this->defaultErrorCode();
    }

    final public function getCategory(): ErrorCategoryInterface
    {
        return $this->escalatedCategory ?? $this->defaultCategory();
    }

    final public function getHttpStatus(): int
    {
        return $this->escalatedHttpStatus
               ?? $this->httpStatusOverride
                  ?? $this->defaultHttpStatus();
    }

    final public function isSafe(): bool
    {
        return $this->isSafeOverride ?? $this->defaultIsSafe();
    }

    final public function isRetryable(): bool
    {
        return $this->isRetryableOverride ?? $this->defaultIsRetryable();
    }

    /**
     * @return array<string, mixed>
     */
    final public function getMeta(): array
    {
        return $this->meta;
    }
}
