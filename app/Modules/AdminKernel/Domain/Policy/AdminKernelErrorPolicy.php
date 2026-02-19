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
        $categoryId = $category->getValue();
        $codeId = $code->getValue();

        $allowedByCategory = self::allowedCodesByCategory();

        // Category not configured → allow
        if (!isset($allowedByCategory[$categoryId])) {
            return;
        }

        // Category configured but empty → allow all
        if ($allowedByCategory[$categoryId] === []) {
            return;
        }

        if (!in_array($codeId, $allowedByCategory[$categoryId], true)) {
            throw new LogicException(sprintf(
                'Error code "%s" is not allowed for category "%s" in AdminKernel policy.',
                $codeId,
                $categoryId
            ));
        }
    }

    public function severity(
        ErrorCategoryInterface $category
    ): int {
        return DefaultErrorPolicy::default()->severity($category);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function allowedCodesByCategory(): array
    {
        return [
            'VALIDATION' => [
                AdminKernelErrorCodeEnum::INVALID_ARGUMENT->getValue(),
                AdminKernelErrorCodeEnum::BAD_REQUEST->getValue(),
            ],
            'AUTHORIZATION' => [
                AdminKernelErrorCodeEnum::PERMISSION_DENIED->getValue(),
                AdminKernelErrorCodeEnum::FORBIDDEN->getValue(),
                AdminKernelErrorCodeEnum::STEP_UP_REQUIRED->getValue(),
            ],
            'AUTHENTICATION' => [
                AdminKernelErrorCodeEnum::UNAUTHORIZED->getValue(),
            ],
            'NOT_FOUND' => [
                AdminKernelErrorCodeEnum::RESOURCE_NOT_FOUND->getValue(),
                AdminKernelErrorCodeEnum::NOT_FOUND->getValue(),
            ],
            'CONFLICT' => [
                AdminKernelErrorCodeEnum::ENTITY_ALREADY_EXISTS->getValue(),
                AdminKernelErrorCodeEnum::ENTITY_IN_USE->getValue(),
            ],
            'UNSUPPORTED' => [
                AdminKernelErrorCodeEnum::INVALID_OPERATION->getValue(),
                AdminKernelErrorCodeEnum::METHOD_NOT_ALLOWED->getValue(),
            ],
            'BUSINESS_RULE' => [
                AdminKernelErrorCodeEnum::DOMAIN_NOT_ALLOWED->getValue(),
            ],
            'SYSTEM' => [
                AdminKernelErrorCodeEnum::INTERNAL_ERROR->getValue(),
            ],
        ];
    }
}
