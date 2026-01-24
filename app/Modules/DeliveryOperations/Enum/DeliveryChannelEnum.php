<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Enum;

enum DeliveryChannelEnum: string
{
    case EMAIL = 'EMAIL';
    case SMS = 'SMS';
    case PUSH = 'PUSH';
    case WEBHOOK = 'WEBHOOK';
    case QUEUE = 'QUEUE';
}
