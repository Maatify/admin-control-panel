<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Application\Contracts\AuthoritativeAuditRecorderInterface;
use App\Context\RequestContext;
use Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder;

class AuthoritativeAuditMaatifyAdapter implements AuthoritativeAuditRecorderInterface
{
    public function __construct(
        private AuthoritativeAuditRecorder $recorder,
        private RequestContext $requestContext
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
        $correlationId = $this->requestContext->getRequestId();

        if ($correlationId === null) {
            throw new \RuntimeException('AuthoritativeAudit Failure: No Correlation ID available in RequestContext.');
        }

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
