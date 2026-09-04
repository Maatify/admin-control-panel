<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Notification\History;

use DateTimeImmutable;
use JsonSerializable;

final class AdminNotificationHistoryViewDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $notificationId,
        public readonly string $notificationType,
        public readonly string $channel,
        public readonly DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $readAt,
        public readonly bool $isRead
    ) {
    }

    /**
     * @return array{
     *     notification_id: int,
     *     notification_type: string,
     *     channel: string,
     *     created_at: string,
     *     read_at: ?string,
     *     is_read: bool
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'notification_type' => $this->notificationType,
            'channel' => $this->channel,
            'created_at' => $this->createdAt->format(DateTimeImmutable::ATOM),
            'read_at' => $this->readAt?->format(DateTimeImmutable::ATOM),
            'is_read' => $this->isRead,
        ];
    }
}
