<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Contract;

use Maatify\DeliveryOperations\DTO\DeliveryOperationRecordDTO;
use Maatify\DeliveryOperations\Exception\DeliveryOperationsStorageException;

interface DeliveryOperationsLoggerInterface
{
    /**
     * @throws DeliveryOperationsStorageException
     */
    public function log(DeliveryOperationRecordDTO $dto): void;
}
