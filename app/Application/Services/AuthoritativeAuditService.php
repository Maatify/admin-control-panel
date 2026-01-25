<?php

declare(strict_types=1);

namespace App\Application\Services;

use Maatify\AuthoritativeAudit\Enum\AuthoritativeAuditActorTypeInterface;
use Maatify\AuthoritativeAudit\Enum\AuthoritativeAuditRiskLevelEnum;
use Maatify\AuthoritativeAudit\Exception\AuthoritativeAuditStorageException;
use Maatify\AuthoritativeAudit\Recorder\AuthoritativeAuditRecorder;
use InvalidArgumentException;

class AuthoritativeAuditService
{
    public function __construct(
        private readonly AuthoritativeAuditRecorder $recorder
    ) {
    }

    /**
     * Records an authoritative audit event (compliance/governance).
     *
     * This method acts as a project-facing wrapper for the AuthoritativeAuditRecorder.
     * It enforces **Fail-Closed** behavior, meaning any exception during validation
     * or storage is PROPAGATED and MUST result in the transaction being aborted.
     *
     * @param string $action
     * @param string $targetType
     * @param int|null $targetId
     * @param AuthoritativeAuditRiskLevelEnum|string $riskLevel
     * @param AuthoritativeAuditActorTypeInterface|string $actorType
     * @param int|null $actorId
     * @param array<mixed> $payload
     * @param string $correlationId
     * @throws AuthoritativeAuditStorageException If storage fails (Fail-Closed)
     * @throws InvalidArgumentException If validation fails (Fail-Closed)
     */
    public function record(
        string $action,
        string $targetType,
        ?int $targetId,
        AuthoritativeAuditRiskLevelEnum|string $riskLevel,
        AuthoritativeAuditActorTypeInterface|string $actorType,
        ?int $actorId,
        array $payload,
        string $correlationId
    ): void {
        $this->recorder->record(
            $action,
            $targetType,
            $targetId,
            $riskLevel,
            $actorType,
            $actorId,
            $payload,
            $correlationId
        );
    }
}
