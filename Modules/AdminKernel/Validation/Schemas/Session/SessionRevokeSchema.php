<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Session;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
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
