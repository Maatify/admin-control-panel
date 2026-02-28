<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
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
                v::stringType()
                    ->notEmpty()
                    ->length(1, 255),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            // ─────────────────────────────
            // Meta Title (Optional)
            // ─────────────────────────────
            'meta_title' => [
                v::stringType()->length(0, 255),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            // ─────────────────────────────
            // Meta Description (Optional)
            // ─────────────────────────────
            'meta_description' => [
                v::stringType()->length(0, 5000),
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