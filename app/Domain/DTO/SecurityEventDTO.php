<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use DateTimeImmutable;

readonly class SecurityEventDTO
{
    /**
     * @param array<string, scalar> $context
     */
    public function __construct(
        public ?int $adminId,
        public string $eventName,
        public array $context,
        public ?string $ipAddress,
        public ?string $userAgent,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
