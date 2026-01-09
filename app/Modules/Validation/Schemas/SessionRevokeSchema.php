<?php

declare(strict_types=1);

namespace App\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

class SessionRevokeSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'session_id' => [v::stringType()->notEmpty(), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
