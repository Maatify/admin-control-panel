<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use DateTimeImmutable;

readonly class FailedNotificationDTO
{
    public function __construct(
        public int $id,
        public string $channel,
        public string $recipient,
        public string $message,
        public string $reason,
        public int $attempts,
        public DateTimeImmutable $failedAt
    ) {
    }
}
