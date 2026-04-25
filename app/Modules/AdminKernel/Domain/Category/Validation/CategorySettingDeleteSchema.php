<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Category\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class CategorySettingDeleteSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'key' => [
                v::stringType()->notEmpty()->length(1, 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
        ];
    }
}

