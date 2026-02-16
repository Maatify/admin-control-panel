<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Validation;

use Maatify\Exceptions\Enum\ErrorCodeEnum;

class InvalidArgumentMaatifyException extends ValidationMaatifyException
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::INVALID_ARGUMENT;
    }
}
