<?php

declare(strict_types=1);

namespace App\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
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
