<?php

declare(strict_types=1);

namespace Maatify\I18n\Domain\Policy;

use LogicException;
use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Contracts\ErrorPolicyInterface;
use Maatify\Exceptions\Policy\DefaultErrorPolicy;
use Maatify\I18n\Domain\Enum\I18nErrorCodeEnum;

final class I18nErrorPolicy implements ErrorPolicyInterface
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
                'Error code "%s" is not allowed for category "%s" in I18n policy.',
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
            'BUSINESS_RULE' => [
                I18nErrorCodeEnum::DOMAIN_NOT_ALLOWED->getValue(),
                I18nErrorCodeEnum::DOMAIN_SCOPE_VIOLATION->getValue(),
                I18nErrorCodeEnum::SCOPE_NOT_ALLOWED->getValue(),
            ],
            'CONFLICT' => [
                I18nErrorCodeEnum::TRANSLATION_KEY_ALREADY_EXISTS->getValue(),
            ],
            'NOT_FOUND' => [
                I18nErrorCodeEnum::TRANSLATION_KEY_NOT_FOUND->getValue(),
            ],
            'SYSTEM' => [
                I18nErrorCodeEnum::TRANSLATION_KEY_CREATE_FAILED->getValue(),
                I18nErrorCodeEnum::TRANSLATION_UPDATE_FAILED->getValue(),
                I18nErrorCodeEnum::TRANSLATION_UPSERT_FAILED->getValue(),
                I18nErrorCodeEnum::TRANSLATION_WRITE_FAILED->getValue(),
            ],
        ];
    }
}
