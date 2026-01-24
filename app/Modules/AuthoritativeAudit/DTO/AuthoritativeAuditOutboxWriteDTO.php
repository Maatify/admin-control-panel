<?php

declare(strict_types=1);

namespace Maatify\AuthoritativeAudit\DTO;

use DateTimeImmutable;

readonly class AuthoritativeAuditOutboxWriteDTO
{
    /**
     * @param string $eventId
     * @param string $actorType
     * @param int|null $actorId
     * @param string $action
     * @param string $targetType
     * @param int|null $targetId
     * @param string $riskLevel
     * @param array<mixed> $payload
     * @param string $correlationId
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        public string $eventId,
        public string $actorType,
        public ?int $actorId,
        public string $action,
        public string $targetType,
        public ?int $targetId,
        public string $riskLevel,
        public array $payload,
        public string $correlationId,
        public DateTimeImmutable $createdAt
    ) {
    }
}
