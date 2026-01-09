<?php

declare(strict_types=1);

namespace App\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

class AdminLookupEmailSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'email' => [v::email(), ValidationErrorCodeEnum::INVALID_EMAIL],
        ];
    }
}
