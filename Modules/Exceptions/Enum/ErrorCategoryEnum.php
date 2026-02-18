<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Enum;

use Maatify\Exceptions\Contracts\ErrorCategoryInterface;

enum ErrorCategoryEnum: string implements ErrorCategoryInterface
{
    case VALIDATION = 'VALIDATION';
    case AUTHENTICATION = 'AUTHENTICATION';
    case AUTHORIZATION = 'AUTHORIZATION';
    case CONFLICT = 'CONFLICT';
    case NOT_FOUND = 'NOT_FOUND';
    case BUSINESS_RULE = 'BUSINESS_RULE';
    case UNSUPPORTED = 'UNSUPPORTED';
    case SYSTEM = 'SYSTEM';
    case RATE_LIMIT = 'RATE_LIMIT';

    case SECURITY = 'SECURITY';

    public function getValue(): string
    {
        return $this->value;
    }
}
