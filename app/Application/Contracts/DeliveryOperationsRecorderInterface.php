<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface DeliveryOperationsRecorderInterface
{
    public function record(
        string $channel,
        string $operationType,
        string $status,
        ?int $targetId = null,
        ?string $providerMessageId = null,
        ?int $attemptNo = 0,
        ?array $metadata = null
    ): void;
}
