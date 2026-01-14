<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use DateTimeImmutable;

readonly class LegacyAuditEventDTO
{
    /**
     * @param array<string, scalar> $changes
     */
    public function __construct(
        public ?int $actorAdminId,
        public string $targetType,
        public ?int $targetId,
        public string $action,
        public array $changes,
        public ?string $ipAddress,
        public ?string $userAgent,
        public DateTimeImmutable $occurredAt,
        public ?string $requestId = null,
        public ?string $routeName = null,
        public string $severity = 'info'
    ) {
    }
}
