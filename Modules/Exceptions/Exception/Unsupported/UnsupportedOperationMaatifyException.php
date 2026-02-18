<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Unsupported;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCodeEnum;

class UnsupportedOperationMaatifyException extends UnsupportedMaatifyException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::UNSUPPORTED_OPERATION;
    }
}
