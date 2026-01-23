<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Enum;

enum SecuritySignalActorTypeEnum: string
{
    case SYSTEM = 'SYSTEM';
    case ADMIN = 'ADMIN';
    case USER = 'USER';
    case SERVICE = 'SERVICE';
    case API_CLIENT = 'API_CLIENT';
    case ANONYMOUS = 'ANONYMOUS';
}
