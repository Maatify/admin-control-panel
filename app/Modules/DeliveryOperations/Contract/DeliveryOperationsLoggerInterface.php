<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Contract;

use Maatify\DeliveryOperations\DTO\DeliveryOperationRecordDTO;

interface DeliveryOperationsLoggerInterface
{
    public function log(DeliveryOperationRecordDTO $dto): void;
}
