<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class ContentDocumentTypesCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            // ─────────────────────────────
            // Technical document type key
            // ─────────────────────────────
            'key' => [
                v::stringType()
                    ->notEmpty()
                    ->length(3, 64)
                    ->regex('/^[a-z0-9\-]+$/'),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            // ─────────────────────────────
            // Required flags
            // ─────────────────────────────
            'requires_acceptance_default' => [
                v::boolType(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            'is_system' => [
                v::boolType(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],
        ];
    }
}