<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum NotificationChannelType: string
{
    case EMAIL = 'email';
    case TELEGRAM = 'telegram';
    case WEBHOOK = 'webhook';
}
