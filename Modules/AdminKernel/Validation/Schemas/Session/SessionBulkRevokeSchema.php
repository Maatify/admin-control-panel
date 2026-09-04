<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Session;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

class SessionBulkRevokeSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'session_ids' => [v::arrayType()->notEmpty()->each(v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
