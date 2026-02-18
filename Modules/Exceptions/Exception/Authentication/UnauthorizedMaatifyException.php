<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Authentication;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCodeEnum;

class UnauthorizedMaatifyException extends AuthenticationMaatifyException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::UNAUTHORIZED;
    }
}
