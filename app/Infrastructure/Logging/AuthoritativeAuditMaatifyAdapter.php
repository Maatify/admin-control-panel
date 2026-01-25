<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Contracts\AuthoritativeAuditRecorderInterface;
use Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder;

class AuthoritativeAuditMaatifyAdapter implements AuthoritativeAuditRecorderInterface
{
    public function __construct(
        private AuthoritativeAuditRecorder $recorder
    ) {
    }

    public function record(
        string $action,
        string $targetType,
        ?int $targetId,
        string $riskLevel,
        string $actorType,
        ?int $actorId,
        array $payload
    ): void {
        $this->recorder->record(
            action: $action,
            targetType: $targetType,
            targetId: $targetId,
            riskLevel: $riskLevel,
            actorType: $actorType,
            actorId: $actorId,
            payload: $payload
        );
    }
}
