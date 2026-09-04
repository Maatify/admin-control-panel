<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class ContentDocumentVersionsCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            // ─────────────────────────────
            // Version (technical identifier)
            // e.g. v1.0.0 / 2026-01 / 1.0
            // ─────────────────────────────
            'version' => [
                v::stringType()
                    ->notEmpty()
                    ->length(1, 32)
                    ->regex('/^[a-zA-Z0-9.\-_]+$/'),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            // ─────────────────────────────
            // Requires Acceptance flag
            // ─────────────────────────────
            'requires_acceptance' => [
                v::boolType(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],
        ];
    }
}