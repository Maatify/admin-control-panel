<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Roles;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

class RolePermissionUnassignSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            // ─────────────────────────────
            // Permission ID
            // ─────────────────────────────
            'permission_id' => [
                v::intType()->positive(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],
        ];
    }
}
