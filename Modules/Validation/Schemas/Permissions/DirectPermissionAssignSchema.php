<?php

declare(strict_types=1);

namespace Maatify\Validation\Schemas\Permissions;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class DirectPermissionAssignSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'permission_id' => [
                v::intType()->positive(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            'is_allowed' => [
                v::boolType(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            'expires_at' => [
                v::optional(v::dateTime('Y-m-d H:i:s')),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],
        ];
    }
}
