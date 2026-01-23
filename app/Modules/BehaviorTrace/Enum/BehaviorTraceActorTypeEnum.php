<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Enum;

enum BehaviorTraceActorTypeEnum: string
{
    case SYSTEM = 'SYSTEM';
    case ADMIN = 'ADMIN';
    case USER = 'USER';
    case SERVICE = 'SERVICE';
    case API_CLIENT = 'API_CLIENT';
    case ANONYMOUS = 'ANONYMOUS';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
