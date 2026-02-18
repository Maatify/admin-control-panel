<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Enum;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;

enum ErrorCodeEnum: string implements ErrorCodeInterface
{
    case MAATIFY_ERROR = 'MAATIFY_ERROR';

    case INVALID_ARGUMENT = 'INVALID_ARGUMENT';
    case BUSINESS_RULE_VIOLATION = 'BUSINESS_RULE_VIOLATION';

    case RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    case CONFLICT = 'CONFLICT';

    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';

    case UNSUPPORTED_OPERATION = 'UNSUPPORTED_OPERATION';

    case DATABASE_CONNECTION_FAILED = 'DATABASE_CONNECTION_FAILED';

    case TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';
    case SESSION_EXPIRED = 'SESSION_EXPIRED';

    public function getValue(): string
    {
        return $this->value;
    }
}
