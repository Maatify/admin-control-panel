<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Admin;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\BooleanRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

class AdminPreferenceUpsertSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'admin_id' => [v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'notification_type' => [v::stringType()->notEmpty(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'channel_type' => [v::stringType()->notEmpty(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'is_enabled' => [BooleanRule::required(), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
