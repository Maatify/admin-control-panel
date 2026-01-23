<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Enum;

enum DeliveryStatusEnum: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case RETRYING = 'retrying';
    case CANCELLED = 'cancelled';
}
