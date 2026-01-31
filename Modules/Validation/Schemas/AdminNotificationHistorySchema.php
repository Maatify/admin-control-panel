<?php

declare(strict_types=1);

namespace Maatify\Validation\Schemas;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
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
            'is_read' => [v::optional(v::boolVal()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'from_date' => [v::optional(v::date()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'to_date' => [v::optional(v::date()), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
