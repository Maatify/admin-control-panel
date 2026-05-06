<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;
use Maatify\Validation\Rules\Primitive\StrictBooleanRule;

final class ContentDocumentTypesUpdateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            // ─────────────────────────────
            // Optional flags
            // ─────────────────────────────
            'requires_acceptance_default' => [
                StrictBooleanRule::required(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            'is_system' => [
                StrictBooleanRule::required(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],
        ];
    }
}