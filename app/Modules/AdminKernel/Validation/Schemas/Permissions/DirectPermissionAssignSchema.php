<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Permissions;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;
use Maatify\Validation\Rules\Primitive\StrictEntityIdRule;
use Maatify\Validation\Rules\Primitive\StrictBooleanRule;

final class DirectPermissionAssignSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'permission_id' => [
                StrictEntityIdRule::required(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            'is_allowed' => [
                StrictBooleanRule::required(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            'expires_at' => [
                v::optional(v::dateTime('Y-m-d H:i:s')),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],
        ];
    }
}
