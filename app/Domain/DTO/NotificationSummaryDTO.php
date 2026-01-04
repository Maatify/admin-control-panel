<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use DateTimeImmutable;
use JsonSerializable;

readonly class NotificationSummaryDTO implements JsonSerializable
{
    public function __construct(
        public int $notificationId,
        public ?int $adminId,
        public string $channel,
        public string $status,
        public string $title,
        public ?string $body,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $deliveredAt
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'admin_id' => $this->adminId,
            'channel' => $this->channel,
            'status' => $this->status,
            'title' => $this->title,
            'body' => $this->body,
            'created_at' => $this->createdAt->format(DateTimeImmutable::ATOM),
            'delivered_at' => $this->deliveredAt?->format(DateTimeImmutable::ATOM),
        ];
    }
}
