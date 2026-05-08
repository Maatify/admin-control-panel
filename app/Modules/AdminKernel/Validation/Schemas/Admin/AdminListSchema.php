<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Admin;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Maatify\Validation\Rules\PaginationRule;
use Respect\Validation\Validator as v;

class AdminListSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'page' => [PaginationRule::page(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'per_page' => [PaginationRule::perPage(100), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'id' => [v::optional(v::intVal()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'email' => [v::optional(v::stringType()), ValidationErrorCodeEnum::INVALID_EMAIL],
        ];
    }
}
