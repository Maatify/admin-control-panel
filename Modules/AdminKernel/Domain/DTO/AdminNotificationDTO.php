<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

use DateTimeImmutable;

final class AdminNotificationDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly string $severity,
        public readonly DateTimeImmutable $occurredAt
    ) {
    }
}
