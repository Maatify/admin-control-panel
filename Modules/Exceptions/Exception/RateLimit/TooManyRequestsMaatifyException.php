<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\RateLimit;

use Maatify\Exceptions\Enum\ErrorCodeEnum;

class TooManyRequestsMaatifyException extends RateLimitMaatifyException
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::TOO_MANY_REQUESTS;
    }
}
