<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class I18nDomainUpdateMetadataSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'name' => [
                StringRule::optional(min: 1, max: 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'description' => [
                StringRule::optional(min: 0, max: 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
