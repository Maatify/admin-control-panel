<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Currency\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class CurrencyTranslationDeleteSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
