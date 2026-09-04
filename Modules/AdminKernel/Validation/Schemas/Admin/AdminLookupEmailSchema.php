<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Admin;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\EmailRule;
use Maatify\Validation\Schemas\AbstractSchema;

class AdminLookupEmailSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'email' => [EmailRule::required(), ValidationErrorCodeEnum::INVALID_EMAIL],
        ];
    }
}
