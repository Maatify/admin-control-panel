<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum VerificationCodeStatus: string
{
    case ACTIVE = 'active';
    case USED = 'used';
    case EXPIRED = 'expired';
}
