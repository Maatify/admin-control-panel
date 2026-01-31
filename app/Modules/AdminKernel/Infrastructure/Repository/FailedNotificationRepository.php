<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository;

use Maatify\AdminKernel\Domain\Contracts\FailedNotificationRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Notification\NotificationDeliveryDTO;
use PDO;

class FailedNotificationRepository implements FailedNotificationRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function recordFailure(NotificationDeliveryDTO $notification, string $reason): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO failed_notifications
            (channel, recipient, message, reason, attempts, last_attempt_at, created_at)
            VALUES (:channel, :recipient, :message, :reason, 1, NOW(), NOW())'
        );

        $message = sprintf(
            "Title: %s\n\nBody: %s",
            $notification->title,
            $notification->body
        );

        $stmt->execute([
            ':channel' => $notification->channel,
            ':recipient' => $notification->recipient,
            ':message' => $message,
            ':reason' => $reason,
        ]);
    }

    public function markRetried(int $failureId): void
    {
        // For now, this might just involve deleting it or marking it as resolved/retried.
        // The prompt asks for "markRetried", but doesn't specify if it should delete or update state.
        // Usually, dead-letter storage implies we keep it until processed.
        // Given the simplistic schema (no status column), maybe we increment attempts if we were retrying?
        // But the prompt says "NO retry logic yet".
        // "markRetried" suggests we *have* retried it.
        // Without a status column, I'll assume we might delete it if retried successfully, or update attempts?
        // Wait, "attempts (int, default 1)" is in schema.
        // So maybe markRetried increments attempts?
        // But usually markRetried implies "we are trying again".
        // Let's implement it as incrementing attempts and updating last_attempt_at.

        $stmt = $this->pdo->prepare(
            'UPDATE failed_notifications
            SET attempts = attempts + 1, last_attempt_at = NOW()
            WHERE id = :id'
        );

        $stmt->execute([':id' => $failureId]);
    }
}
