<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum RoleLevel: int
{
    case SUPER_ADMIN = 100;
    case ADMIN = 80;
    case MANAGER = 50;
    case SUPPORT = 20;
    case USER = 10;
    case UNKNOWN = 0;
}
