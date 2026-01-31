<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Enum;

/**
 * Internal enum for categorizing verification failures in logs.
 *
 * @internal This enum is for internal logging and diagnostics only.
 *           It is NOT part of the public API or domain contract.
 *           Do not rely on these values for business logic flow control.
 */
enum VerificationFailureReasonEnum: string
{
    case INVALID_OTP = 'invalid_otp';
    case OTP_WRONG_PURPOSE = 'otp_wrong_purpose';
    case IDENTITY_MISMATCH = 'identity_mismatch';
    case INVALID_IDENTITY_ID = 'invalid_identity_id';
    case CHANNEL_ALREADY_LINKED = 'channel_already_linked';
    case CHANNEL_REGISTRATION_FAILED = 'channel_registration_failed';
}
