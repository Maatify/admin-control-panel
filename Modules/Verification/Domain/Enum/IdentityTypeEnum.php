<?php

declare(strict_types=1);

namespace Maatify\Verification\Domain\Enum;

enum IdentityTypeEnum: string
{
    case Admin = 'admin';
    case Email = 'email';
}
