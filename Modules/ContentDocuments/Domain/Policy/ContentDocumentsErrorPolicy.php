<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Policy;

use LogicException;
use Maatify\ContentDocuments\Domain\Enum\ContentDocumentsErrorCodeEnum;
use Maatify\Exceptions\Contracts\ErrorCategoryInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Contracts\ErrorPolicyInterface;
use Maatify\Exceptions\Policy\DefaultErrorPolicy;

final class ContentDocumentsErrorPolicy implements ErrorPolicyInterface
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
        $categoryId = $category->getValue();
        $codeId = $code->getValue();

        $allowedByCategory = self::allowedCodesByCategory();

        // Category not configured → allow (keeps policy extensible)
        if (!isset($allowedByCategory[$categoryId])) {
            return;
        }

        // Category configured but empty list → allow all
        if ($allowedByCategory[$categoryId] === []) {
            return;
        }

        if (!in_array($codeId, $allowedByCategory[$categoryId], true)) {
            throw new LogicException(sprintf(
                'Error code "%s" is not allowed for category "%s" in ContentDocuments policy.',
                $codeId,
                $categoryId
            ));
        }
    }

    public function severity(ErrorCategoryInterface $category): int
    {
        // Keep the global severity semantics consistent with the shared defaults
        return self::defaultSeverityPolicy()->severity($category);
    }

    private static function defaultSeverityPolicy(): DefaultErrorPolicy
    {
        static $default = null;

        return $default ??= DefaultErrorPolicy::default();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function allowedCodesByCategory(): array
    {
        return [
            'NOT_FOUND' => [
                ContentDocumentsErrorCodeEnum::DOCUMENT_NOT_FOUND->getValue(),
                ContentDocumentsErrorCodeEnum::DOCUMENT_TYPE_NOT_FOUND->getValue(),
                ContentDocumentsErrorCodeEnum::DOCUMENT_TRANSLATION_NOT_FOUND->getValue(),
            ],
            'CONFLICT' => [
                ContentDocumentsErrorCodeEnum::DOCUMENT_ACTIVATION_CONFLICT->getValue(),
                ContentDocumentsErrorCodeEnum::DOCUMENT_ALREADY_ACCEPTED->getValue(),
                ContentDocumentsErrorCodeEnum::DOCUMENT_TRANSLATION_ALREADY_EXISTS->getValue(),
                ContentDocumentsErrorCodeEnum::DOCUMENT_TYPE_ALREADY_EXISTS->getValue(),
                ContentDocumentsErrorCodeEnum::DOCUMENT_VERSION_ALREADY_EXISTS->getValue(),
            ],
            'BUSINESS_RULE' => [
                ContentDocumentsErrorCodeEnum::DOCUMENT_VERSION_IMMUTABLE->getValue(),
                ContentDocumentsErrorCodeEnum::INVALID_DOCUMENT_STATE->getValue(),
            ],
            'VALIDATION' => [
                ContentDocumentsErrorCodeEnum::INVALID_ACTOR_IDENTITY->getValue(),
                ContentDocumentsErrorCodeEnum::INVALID_DOCUMENT_TYPE_KEY->getValue(),
                ContentDocumentsErrorCodeEnum::INVALID_DOCUMENT_VERSION->getValue(),
            ],
        ];
    }
}
