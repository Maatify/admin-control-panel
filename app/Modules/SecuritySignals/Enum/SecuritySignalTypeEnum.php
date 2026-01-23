<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Enum;

enum SecuritySignalTypeEnum: string
{
    case LOGIN_FAILED = 'login_failed';
    case LOGIN_BLOCKED = 'login_blocked';
    case LOGIN_THROTTLED = 'login_throttled';
    case PERMISSION_DENIED = 'permission_denied';
    case SESSION_INVALID = 'session_invalid';
    case SESSION_EXPIRED = 'session_expired';
    case STEP_UP_FAILED = 'step_up_failed';
    case RECOVERY_BLOCKED = 'recovery_blocked';
    case SUSPICIOUS_ACTIVITY = 'suspicious_activity';
}
