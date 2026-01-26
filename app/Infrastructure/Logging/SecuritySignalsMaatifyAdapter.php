<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Contracts\SecuritySignalsRecorderInterface;
use Maatify\SecuritySignals\Recorder\SecuritySignalsRecorder;

class SecuritySignalsMaatifyAdapter implements SecuritySignalsRecorderInterface
{
    public function __construct(
        private SecuritySignalsRecorder $recorder
    ) {
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function record(
        string $signalType,
        string $severity,
        string $actorType,
        ?int $actorId,
        ?string $ipAddress,
        ?string $userAgent,
        ?array $metadata = null
    ): void {
        $this->recorder->record(
            signalType: $signalType,
            severity: $severity,
            actorType: $actorType,
            actorId: $actorId,
            metadata: $metadata,
            correlationId: null,
            requestId: null,
            routeName: null,
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );
    }
}
