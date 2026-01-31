<?php

declare(strict_types=1);

namespace Maatify\Validation\Schemas;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

class StepUpVerifySchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'code' => [v::stringType()->notEmpty(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'scope' => [v::optional(v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
