<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum VerificationFailureReasonEnum: string
{
    case INVALID_OTP = 'invalid_otp';
    case OTP_WRONG_PURPOSE = 'otp_wrong_purpose';
    case IDENTITY_MISMATCH = 'identity_mismatch';
    case INVALID_IDENTITY_ID = 'invalid_identity_id';
    case CHANNEL_ALREADY_LINKED = 'channel_already_linked';
    case CHANNEL_REGISTRATION_FAILED = 'channel_registration_failed';
}
