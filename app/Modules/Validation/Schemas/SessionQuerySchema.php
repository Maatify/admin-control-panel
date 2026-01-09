<?php

declare(strict_types=1);

namespace App\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

class SessionQuerySchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'page' => [v::optional(v::intVal()->min(1)), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'per_page' => [v::optional(v::intVal()->min(1)->max(100)), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'filters' => [v::optional(v::arrayType()), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
