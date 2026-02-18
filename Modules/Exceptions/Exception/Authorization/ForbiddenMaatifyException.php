<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Authorization;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCodeEnum;

class ForbiddenMaatifyException extends AuthorizationMaatifyException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::FORBIDDEN;
    }
}
