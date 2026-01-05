<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum SessionState: string
{
    case NO_SESSION = 'NO_SESSION';
    case PENDING_STEP_UP = 'PENDING_STEP_UP';
    case ACTIVE = 'ACTIVE';
    case REVOKED = 'REVOKED';
}
