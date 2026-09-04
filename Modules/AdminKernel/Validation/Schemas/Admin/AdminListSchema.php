<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Admin;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

class AdminListSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'page' => [v::optional(v::intVal()->min(1)), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'per_page' => [v::optional(v::intVal()->min(1)->max(100)), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'id' => [v::optional(v::intVal()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'email' => [v::optional(v::stringType()), ValidationErrorCodeEnum::INVALID_EMAIL],
        ];
    }
}
