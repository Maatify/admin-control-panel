<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Roles;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;
use Maatify\Validation\Rules\Primitive\StrictEntityIdRule;

class RoleAdminUnassignSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'admin_id' => [
                StrictEntityIdRule::required(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],
        ];
    }
}
