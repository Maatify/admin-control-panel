<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Contracts\AuthoritativeAuditRecorderInterface;
use App\Infrastructure\Context\CorrelationIdProviderInterface;
use Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder;

class AuthoritativeAuditMaatifyAdapter implements AuthoritativeAuditRecorderInterface
{
    public function __construct(
        private AuthoritativeAuditRecorder $recorder,
        private CorrelationIdProviderInterface $correlationIdProvider
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function record(
        string $action,
        string $targetType,
        ?int $targetId,
        string $riskLevel,
        string $actorType,
        ?int $actorId,
        array $payload
    ): void {
        $correlationId = $this->correlationIdProvider->getCorrelationId();

        $this->recorder->record(
            action: $action,
            targetType: $targetType,
            targetId: $targetId,
            riskLevel: $riskLevel,
            actorType: $actorType,
            actorId: $actorId,
            payload: $payload,
            correlationId: $correlationId
        );
    }
}
