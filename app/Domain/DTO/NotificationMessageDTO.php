<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use DateTimeImmutable;

final class NotificationMessageDTO
{
    /**
     * @param array<string, scalar> $context
     */
    public function __construct(
        public readonly string $channel,
        public readonly string $eventName,
        public readonly array $context,
        public readonly ?int $adminId,
        public readonly DateTimeImmutable $occurredAt,
        public readonly string $severity
    ) {
    }
}
