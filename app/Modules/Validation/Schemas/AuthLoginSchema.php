<?php

declare(strict_types=1);

namespace App\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

class AuthLoginSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'email' => [v::email(), ValidationErrorCodeEnum::INVALID_EMAIL],
            'password' => [v::stringType()->notEmpty(), ValidationErrorCodeEnum::INVALID_PASSWORD],
        ];
    }
}
