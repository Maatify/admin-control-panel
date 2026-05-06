<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Permissions;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;
use Maatify\Validation\Rules\Primitive\StrictEntityIdRule;

final class DirectPermissionRevokeSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'permission_id' => [
                StrictEntityIdRule::required(),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],
        ];
    }
}
