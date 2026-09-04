<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Enum;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;

enum AdminKernelErrorCodeEnum: string implements ErrorCodeInterface
{
    case INVALID_ARGUMENT = 'INVALID_ARGUMENT';
    case BAD_REQUEST = 'BAD_REQUEST';
    case PERMISSION_DENIED = 'PERMISSION_DENIED';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    case NOT_FOUND = 'NOT_FOUND';
    case ENTITY_ALREADY_EXISTS = 'ENTITY_ALREADY_EXISTS';
    case ENTITY_IN_USE = 'ENTITY_IN_USE';
    case INVALID_OPERATION = 'INVALID_OPERATION';
    case DOMAIN_NOT_ALLOWED = 'DOMAIN_NOT_ALLOWED';
    case METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
    case STEP_UP_REQUIRED = 'STEP_UP_REQUIRED';

    public function getValue(): string
    {
        return $this->value;
    }
}
