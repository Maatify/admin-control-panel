<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Enum;

enum DeliveryOperationTypeEnum: string
{
    case NOTIFICATION = 'NOTIFICATION';
    case JOB = 'JOB';
    case EXPORT = 'EXPORT'; // Asynchronous export generation, not the user action of requesting it?
    // Wait, the doc says "Delivery Operations: Jobs, queues, notifications, webhooks lifecycle"
    // So general types are fine.
    case WEBHOOK_DISPATCH = 'WEBHOOK_DISPATCH';
    case IMPORT = 'IMPORT';
}
