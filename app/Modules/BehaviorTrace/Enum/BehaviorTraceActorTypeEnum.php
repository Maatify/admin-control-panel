<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Enum;

enum BehaviorTraceActorTypeEnum: string implements BehaviorTraceActorTypeInterface
{
    case SYSTEM = 'SYSTEM';
    case ADMIN = 'ADMIN';
    case USER = 'USER';
    case SERVICE = 'SERVICE';
    case API_CLIENT = 'API_CLIENT';
    case ANONYMOUS = 'ANONYMOUS';

    public function value(): string
    {
        return $this->value;
    }
}
