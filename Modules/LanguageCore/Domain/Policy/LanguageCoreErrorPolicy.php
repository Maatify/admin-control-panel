<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\Domain\Policy;

use LogicException;
use Maatify\LanguageCore\Domain\Enum\LanguageCoreErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Contracts\ErrorPolicyInterface;
use Maatify\Exceptions\Policy\DefaultErrorPolicy;

final class LanguageCoreErrorPolicy implements ErrorPolicyInterface
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
                'Error code "%s" is not allowed for category "%s" in LanguageCore policy.',
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
     * @return array<string,array<int,string>>
     */
    private static function allowedCodesByCategory(): array
    {
        return [
            'CONFLICT' => [
                LanguageCoreErrorCodeEnum::LANGUAGE_ALREADY_EXISTS->getValue(),
            ],
            'NOT_FOUND' => [
                LanguageCoreErrorCodeEnum::LANGUAGE_NOT_FOUND->getValue(),
            ],
            'BUSINESS_RULE' => [
                LanguageCoreErrorCodeEnum::INVALID_LANGUAGE_FALLBACK->getValue(),
            ],
            'SYSTEM' => [
                LanguageCoreErrorCodeEnum::LANGUAGE_CREATE_FAILED->getValue(),
                LanguageCoreErrorCodeEnum::LANGUAGE_UPDATE_FAILED->getValue(),
            ],
        ];
    }
}
