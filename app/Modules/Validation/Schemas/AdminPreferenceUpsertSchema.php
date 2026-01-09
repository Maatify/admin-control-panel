<?php

declare(strict_types=1);

namespace App\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

class AdminPreferenceUpsertSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'admin_id' => [v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'notification_type' => [v::stringType()->notEmpty(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'channel_type' => [v::stringType()->notEmpty(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'is_enabled' => [v::boolVal(), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
