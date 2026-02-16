<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\NotFound;

use Maatify\Exceptions\Enum\ErrorCodeEnum;

class ResourceNotFoundMaatifyException extends NotFoundMaatifyException
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::RESOURCE_NOT_FOUND;
    }
}
