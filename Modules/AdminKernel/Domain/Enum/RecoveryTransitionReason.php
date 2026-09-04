<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Enum;

enum RecoveryTransitionReason: string
{
    case ENVIRONMENT_OVERRIDE = 'environment_override';
    case WEAK_CRYPTO_KEY = 'weak_crypto_key';
    case MANUAL_ADMIN_ACTION = 'manual_admin_action';
    case SYSTEM_STARTUP = 'system_startup';
}
