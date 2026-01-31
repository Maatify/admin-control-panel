<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Enum;

enum VerificationPurposeEnum: string
{
    case EmailVerification = 'email_verification';
    case TelegramChannelLink = 'telegram_channel_link';
}
