<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Enum;

enum DeliveryStatusEnum: string
{
    case QUEUED = 'QUEUED';
    case SENT = 'SENT';
    case DELIVERED = 'DELIVERED';
    case FAILED = 'FAILED';
    case RETRYING = 'RETRYING';
    case FAILED_PERMANENT = 'FAILED_PERMANENT';
}
