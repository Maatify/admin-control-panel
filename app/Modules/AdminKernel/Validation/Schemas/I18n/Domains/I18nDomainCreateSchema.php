<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\I18n\Domains;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class I18nDomainCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'code' => [
                v::stringType()->length(1, 50),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'name' => [
                v::stringType()->length(1, 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'description' => [
                v::optional(v::stringType()->length(0, 255)),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'is_active' => [
                v::optional(v::boolVal()),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
