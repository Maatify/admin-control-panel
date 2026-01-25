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

    /**
     * @param array<string, mixed>|null $metadata
     */
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
            attemptNo: $attemptNo ?? 0,
            actorType: null,
            actorId: null,
            targetType: null,
            targetId: $targetId,
            scheduledAt: null,
            completedAt: null,
            correlationId: null,
            requestId: null,
            provider: null,
            providerMessageId: $providerMessageId,
            errorCode: null,
            errorMessage: null,
            metadata: $metadata
        );
    }
}
