<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class ContentDocumentTranslationsUpsertSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [

            // ─────────────────────────────
            // Title (Required)
            // ─────────────────────────────
            'title' => [
                StringRule::required(min: 1, max: 255),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            // ─────────────────────────────
            // Meta Title (Optional)
            // ─────────────────────────────
            'meta_title' => [
                StringRule::optional(min: 0, max: 255),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            // ─────────────────────────────
            // Meta Description (Optional)
            // ─────────────────────────────
            'meta_description' => [
                StringRule::optional(min: 0, max: 5000),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            // ─────────────────────────────
            // Content (Required HTML)
            // ─────────────────────────────
            'content' => [
                v::stringType()
                    ->notEmpty(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],
        ];
    }
}
