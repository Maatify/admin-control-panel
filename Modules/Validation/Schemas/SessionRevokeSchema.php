<?php

declare(strict_types=1);

namespace Maatify\Validation\Schemas;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
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
