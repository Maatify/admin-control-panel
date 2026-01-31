<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Enum;

enum VerificationCodeStatus: string
{
    case ACTIVE = 'active';
    case USED = 'used';
    case EXPIRED = 'expired';
}
