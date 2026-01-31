<?php

declare(strict_types=1);

namespace Maatify\Validation\Schemas;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

class NotificationQuerySchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'status' => [v::optional(v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'channel' => [v::optional(v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'from' => [v::optional(v::date()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'to' => [v::optional(v::date()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'admin_id' => [v::optional(v::intVal()), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
