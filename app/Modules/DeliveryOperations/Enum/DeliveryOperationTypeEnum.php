<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Enum;

enum DeliveryOperationTypeEnum: string
{
    case NOTIFICATION_SEND = 'notification_send';
    case WEBHOOK_DELIVER = 'webhook_deliver';
    case JOB_RUN = 'job_run';
}
