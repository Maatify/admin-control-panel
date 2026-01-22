<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 09:29
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\SecurityEvents\Enum;

/**
 * Enumerates all supported security event types.
 *
 * These events are observational signals and MUST NOT
 * represent authoritative state changes.
 */
enum SecurityEventTypeEnum: string
{
    // Authentication
    case LOGIN_FAILED = 'login_failed';
    case LOGIN_SUCCEEDED = 'login_succeeded';
    case LOGOUT = 'logout';

    // Step-Up / MFA
    case STEP_UP_FAILED = 'step_up_failed';
    case STEP_UP_SUCCEEDED = 'step_up_succeeded';

    case STEP_UP_NOT_ENROLLED = 'step_up_not_enrolled';
    case STEP_UP_INVALID_CODE = 'step_up_invalid_code';
    case STEP_UP_RISK_MISMATCH = 'step_up_risk_mismatch';
    case STEP_UP_ENROLL_FAILED = 'step_up_enroll_failed';

    case EMAIL_VERIFICATION_FAILED = 'email_verification_failed';
    case EMAIL_VERIFICATION_SUBJECT_NOT_FOUND = 'email_verification_subject_not_found';

    // Authorization
    case PERMISSION_DENIED = 'permission_denied';

    // Session / Token
    case SESSION_INVALID = 'session_invalid';
    case SESSION_EXPIRED = 'session_expired';

    // Recovery / Reset
    case PASSWORD_RESET_REQUESTED = 'password_reset_requested';
    case PASSWORD_RESET_FAILED = 'password_reset_failed';

    case LEGACY_UNMAPPED = 'legacy_unmapped';
}
