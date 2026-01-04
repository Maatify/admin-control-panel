<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use DateTimeImmutable;

final class AdminActivityDTO
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public readonly int $actorAdminId,
        public readonly string $action,
        public readonly string $targetType,
        public readonly ?int $targetId,
        public readonly array $context,
        public readonly DateTimeImmutable $occurredAt
    ) {
    }
}
