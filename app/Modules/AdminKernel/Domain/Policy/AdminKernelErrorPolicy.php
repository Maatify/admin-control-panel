<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Policy;

use LogicException;
use Maatify\AdminKernel\Domain\Enum\AdminKernelErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Contracts\ErrorPolicyInterface;
use Maatify\Exceptions\Policy\DefaultErrorPolicy;

final class AdminKernelErrorPolicy implements ErrorPolicyInterface
{
    private static ?self $instance = null;

    /** @var array<string, list<AdminKernelErrorCodeEnum>> */
    private const ALLOWED_CODES = [
        'VALIDATION' => [
            AdminKernelErrorCodeEnum::INVALID_ARGUMENT,
            AdminKernelErrorCodeEnum::BAD_REQUEST,
        ],
        'AUTHORIZATION' => [
            AdminKernelErrorCodeEnum::PERMISSION_DENIED,
            AdminKernelErrorCodeEnum::FORBIDDEN,
            AdminKernelErrorCodeEnum::STEP_UP_REQUIRED,
        ],
        'AUTHENTICATION' => [
            AdminKernelErrorCodeEnum::UNAUTHORIZED,
        ],
        'NOT_FOUND' => [
            AdminKernelErrorCodeEnum::RESOURCE_NOT_FOUND,
            AdminKernelErrorCodeEnum::NOT_FOUND,
        ],
        'CONFLICT' => [
            AdminKernelErrorCodeEnum::ENTITY_ALREADY_EXISTS,
            AdminKernelErrorCodeEnum::ENTITY_IN_USE,
        ],
        'UNSUPPORTED' => [
            AdminKernelErrorCodeEnum::INVALID_OPERATION,
            AdminKernelErrorCodeEnum::METHOD_NOT_ALLOWED,
        ],
        'BUSINESS_RULE' => [
            AdminKernelErrorCodeEnum::DOMAIN_NOT_ALLOWED,
        ],
        'SYSTEM' => [
            AdminKernelErrorCodeEnum::INTERNAL_ERROR,
        ],
    ];

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function validate(
        ErrorCodeInterface $code,
        ErrorCategoryInterface $category
    ): void {
        if (! $code instanceof AdminKernelErrorCodeEnum) {
            return;
        }

        $categoryValue = $category->getValue();
        $allowed = self::ALLOWED_CODES[$categoryValue] ?? [];

        foreach ($allowed as $allowedCode) {
            if ($allowedCode === $code) {
                return;
            }
        }

        throw new LogicException(
            sprintf(
                'Error code "%s" is not allowed for category "%s".',
                $code->getValue(),
                $categoryValue
            )
        );
    }

    public function severity(
        ErrorCategoryInterface $category
    ): int {
        return DefaultErrorPolicy::default()->severity($category);
    }
}
