<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\Admin;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\BooleanRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

class AdminNotificationHistorySchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'admin_id' => [v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'page' => [v::optional(v::intVal()->min(1)), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'limit' => [v::optional(v::intVal()->min(1)), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'notification_type' => [v::optional(v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'is_read' => [BooleanRule::optional(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'from_date' => [v::optional(v::date()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'to_date' => [v::optional(v::date()), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
