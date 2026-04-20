<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\PositiveEntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class I18nDomainSetActiveSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                PositiveEntityIdRule::rule(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'is_active' => [
                v::boolVal(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}

