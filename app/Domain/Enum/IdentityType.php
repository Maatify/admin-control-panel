<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum IdentityType: string
{
    case ADMIN = 'admin';
    case EMAIL = 'email';
}
