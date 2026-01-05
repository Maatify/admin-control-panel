<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum IdentityTypeEnum: string
{
    case Admin = 'admin';
    case Email = 'email';
}
