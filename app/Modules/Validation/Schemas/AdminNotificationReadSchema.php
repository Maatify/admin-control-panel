<?php

declare(strict_types=1);

namespace App\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

class AdminNotificationReadSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
