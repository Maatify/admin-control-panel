<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Contracts;

interface DeliveryOperationsRecorderInterface
{
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
    ): void;
}
