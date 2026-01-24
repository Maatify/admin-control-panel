<?php

declare(strict_types=1);

namespace App\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

class AdminEmailVerifySchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'emailId' => [v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
