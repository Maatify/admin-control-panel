<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Contracts\DeliveryOperationsRecorderInterface;
use Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder;

class DeliveryOperationsMaatifyAdapter implements DeliveryOperationsRecorderInterface
{
    public function __construct(
        private DeliveryOperationsRecorder $recorder
    ) {
    }

    public function record(
        string $channel,
        string $operationType,
        string $status,
        ?int $targetId = null,
        ?string $providerMessageId = null,
        ?int $attemptNo = 0,
        ?array $metadata = null
    ): void {
        $this->recorder->record(
            channel: $channel,
            operationType: $operationType,
            status: $status,
            targetId: $targetId,
            providerMessageId: $providerMessageId,
            attemptNo: $attemptNo,
            metadata: $metadata
        );
    }
}
