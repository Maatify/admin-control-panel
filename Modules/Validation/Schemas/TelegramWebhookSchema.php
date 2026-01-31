<?php

declare(strict_types=1);

namespace Maatify\Validation\Schemas;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

class TelegramWebhookSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'message' => [v::optional(v::arrayType()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            // We make it optional because Telegram might send updates without message (e.g. edited_message) which we ignore gracefully in controller
            // But if 'message' is present, it must be an array.
        ];
    }
}
