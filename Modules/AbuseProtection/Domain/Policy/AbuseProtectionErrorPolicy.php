<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Domain\Policy;

use LogicException;
use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Contracts\ErrorPolicyInterface;
use Maatify\Exceptions\Policy\DefaultErrorPolicy;
use Maatify\AbuseProtection\Domain\Enum\AbuseProtectionErrorCodeEnum;

final class AbuseProtectionErrorPolicy implements ErrorPolicyInterface
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function validate(
        ErrorCodeInterface $code,
        ErrorCategoryInterface $category
    ): void {
        $allowed = self::allowedCodesByCategory();
        $categoryId = $category->getValue();
        $codeId = $code->getValue();

        if (!isset($allowed[$categoryId])) {
            return;
        }

        if (!in_array($codeId, $allowed[$categoryId], true)) {
            throw new LogicException(sprintf(
                'Error code "%s" is not allowed for category "%s" in AbuseProtection policy.',
                $codeId,
                $categoryId
            ));
        }
    }

    public function severity(ErrorCategoryInterface $category): int
    {
        return DefaultErrorPolicy::default()->severity($category);
    }

    /**
     * @return array<string, list<string>>
     */
    private static function allowedCodesByCategory(): array
    {
        return [
            'SECURITY' => [
                AbuseProtectionErrorCodeEnum::CHALLENGE_REQUIRED->getValue(),
            ],
        ];
    }
}
