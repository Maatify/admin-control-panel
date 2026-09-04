<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Admin;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

class AdminEmailVerifySchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'emailId' => [v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
