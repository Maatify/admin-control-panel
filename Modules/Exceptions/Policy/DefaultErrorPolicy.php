<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Policy;

use LogicException;
use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Contracts\ErrorPolicyInterface;

/**
 * Configurable default policy.
 *
 * - Provides sane defaults
 * - Can be fully overridden
 * - Immutable after construction
 */
final class DefaultErrorPolicy implements ErrorPolicyInterface
{

    /** @var array<string,int> */
    private array $severityRanking;

    /** @var array<string, list<string>> */
    private array $allowedErrorCodes;

    /**
     * @param array<string,int> $severityRanking
     * @param   array<string,list<string>>  $allowedErrorCodes
     */
    public function __construct(
        array $severityRanking,
        array $allowedErrorCodes
    ) {
        $this->severityRanking = $severityRanking;
        $this->allowedErrorCodes = $allowedErrorCodes;
    }

    /**
     * Returns default immutable policy instance.
     */
    public static function default(): self
    {
        return new self(
            self::defaultSeverityRanking(),
            self::defaultAllowedCodes()
        );
    }

    /**
     * Create policy with partial overrides.
     *
     * @param array<string,int> $severityOverrides
     * @param array<string,array<string>> $allowedOverrides
     */
    public static function withOverrides(
        array $severityOverrides = [],
        array $allowedOverrides = []
    ): self {
        return new self(
            array_replace(self::defaultSeverityRanking(), $severityOverrides),
            array_replace_recursive(self::defaultAllowedCodes(), $allowedOverrides)
        );
    }

    // -----------------------------------------------------

    public function validate(
        ErrorCodeInterface $code,
        ErrorCategoryInterface $category
    ): void {
        $categoryId = $category->getValue();
        $codeId = $code->getValue();

        // Category not configured → allow
        if (!isset($this->allowedErrorCodes[$categoryId])) {
            return;
        }

        // Category configured but no restriction → allow
        if ($this->allowedErrorCodes[$categoryId] === []) {
            return;
        }

        if (!in_array($codeId, $this->allowedErrorCodes[$categoryId], true)) {
            throw new LogicException(
                sprintf(
                    'Error code "%s" is not allowed for category "%s".',
                    $codeId,
                    $categoryId
                )
            );
        }
    }

    public function severity(
        ErrorCategoryInterface $category
    ): int {
        return $this->severityRanking[$category->getValue()] ?? 0;
    }

    // -----------------------------------------------------
    // Default Config Definitions
    // -----------------------------------------------------

    /**
     * @return array<string,int>
     */
    private static function defaultSeverityRanking(): array
    {
        return [
            'SYSTEM' => 90,
            'RATE_LIMIT' => 80,
            'AUTHENTICATION' => 70,
            'AUTHORIZATION' => 60,
            'VALIDATION' => 50,
            'BUSINESS_RULE' => 40,
            'CONFLICT' => 30,
            'NOT_FOUND' => 20,
            'UNSUPPORTED' => 10,
            'SECURITY' => 85
        ];
    }

    /**
     * @return array<string,list<string>>
     */
    private static function defaultAllowedCodes(): array
    {
        return [
            'VALIDATION' => ['INVALID_ARGUMENT'],
            'AUTHENTICATION' => [
                'UNAUTHORIZED',
                'SESSION_EXPIRED',
                'AUTH_STATE_VIOLATION',
                'RECOVERY_LOCKED',
            ],
            'AUTHORIZATION' => ['FORBIDDEN'],
            'CONFLICT' => ['CONFLICT', 'ENTITY_IN_USE'],
            'NOT_FOUND' => ['RESOURCE_NOT_FOUND'],
            'BUSINESS_RULE' => ['BUSINESS_RULE_VIOLATION'],
            'UNSUPPORTED' => ['UNSUPPORTED_OPERATION'],
            'SYSTEM' => ['MAATIFY_ERROR', 'DATABASE_CONNECTION_FAILED'],
            'RATE_LIMIT' => ['TOO_MANY_REQUESTS'],
            'SECURITY' => []
        ];
    }
}
