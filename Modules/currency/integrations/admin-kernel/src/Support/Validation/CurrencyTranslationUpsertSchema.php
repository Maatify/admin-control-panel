<?php

declare(strict_types=1);

namespace Maatify\Currency\Integration\AdminKernel\Support\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class CurrencyTranslationUpsertSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'translated_name' => [
                v::stringType()->notEmpty()->length(1, 50),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
