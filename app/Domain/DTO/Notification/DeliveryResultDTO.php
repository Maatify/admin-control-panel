<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification;

use DateTimeImmutable;

readonly class DeliveryResultDTO
{
    public function __construct(
        public string $notificationId,
        public string $channel,
        public bool $success,
        public ?string $errorReason,
        public DateTimeImmutable $attemptedAt
    ) {
    }
}
