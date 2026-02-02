<?php

declare(strict_types=1);

namespace Maatify\Validation\Schemas\Permissions;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class DirectPermissionRevokeSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'permission_id' => [
                v::intType()->positive(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],
        ];
    }
}
