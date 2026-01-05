<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum VerificationPurpose: string
{
    case EMAIL_VERIFICATION = 'email_verification';
    case TELEGRAM_CHANNEL_LINK = 'telegram_channel_link';
}
