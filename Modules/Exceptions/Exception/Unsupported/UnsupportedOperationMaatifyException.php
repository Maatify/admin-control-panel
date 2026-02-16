<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Unsupported;

use Maatify\Exceptions\Enum\ErrorCodeEnum;

class UnsupportedOperationMaatifyException extends UnsupportedMaatifyException
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::UNSUPPORTED_OPERATION;
    }
}
