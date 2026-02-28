<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class ContentDocumentTypesUpdateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            // ─────────────────────────────
            // Optional flags
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