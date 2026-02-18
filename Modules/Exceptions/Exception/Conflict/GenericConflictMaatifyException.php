<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Exception\Conflict;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCodeEnum;

class GenericConflictMaatifyException extends ConflictMaatifyException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::CONFLICT;
    }
}
