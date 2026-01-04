<?php

declare(strict_types=1);

namespace App\Domain\Notification;

enum NotificationChannelType: string
{
    case EMAIL = 'email';
    case TELEGRAM = 'telegram';
    case WEBHOOK = 'webhook';
}
