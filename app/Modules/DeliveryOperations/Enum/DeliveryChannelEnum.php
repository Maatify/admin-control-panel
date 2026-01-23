<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Enum;

enum DeliveryChannelEnum: string
{
    case EMAIL = 'email';
    case TELEGRAM = 'telegram';
    case SMS = 'sms';
    case WEBHOOK = 'webhook';
    case PUSH = 'push';
    case JOB = 'job';
}
