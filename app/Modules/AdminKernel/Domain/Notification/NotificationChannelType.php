<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Notification;

enum NotificationChannelType: string
{
    case EMAIL = 'email';
    case TELEGRAM = 'telegram';
    case WEBHOOK = 'webhook';
}
