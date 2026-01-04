<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\NotificationReadRepositoryInterface;
use App\Domain\DTO\NotificationSummaryDTO;
use DateTimeImmutable;
use PDO;

class NotificationReadRepository implements NotificationReadRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * @param int $adminId
     * @return array<NotificationSummaryDTO>
     */
    public function findByAdminId(int $adminId): array
    {
        // Current Phase 9.4 schema for 'failed_notifications' does not store admin_id,
        // and 'recipient' is a plaintext string (email/phone) which we cannot safely
        // link back to 'admin_emails' (which uses blind index) without decryption keys
        // or complex logic that belongs in the domain/service layer.
        //
        // As a result, this method currently returns an empty array to satisfy the interface contract.
        // Future phases may add 'admin_id' to the notifications/failures table or provide a linkage map.
        return [];
    }

    /**
     * @param string $status
     * @return array<NotificationSummaryDTO>
     */
    public function findByStatus(string $status): array
    {
        // Currently we only have 'failed_notifications'.
        // If query is for 'failed', return them.
        // If query is for 'sent' or 'delivered', return empty as we don't persist them.

        if ($status !== 'failed') {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT * FROM failed_notifications ORDER BY created_at DESC'
        );
        $stmt->execute();

        $results = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            if (! is_array($row)) {
                continue;
            }

            /**
             * @var array{
             *   id: int|string,
             *   channel: string,
             *   message: string,
             *   created_at: string
             * } $row
             */
            $results[] = $this->mapRowToDTO($row);
        }

        return $results;
    }

    /**
     * @param string $channel
     * @return array<NotificationSummaryDTO>
     */
    public function findByChannel(string $channel): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM failed_notifications WHERE channel = :channel ORDER BY created_at DESC'
        );
        $stmt->execute([':channel' => $channel]);

        $results = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            if (! is_array($row)) {
                continue;
            }

            /**
             * @var array{
             *   id: int|string,
             *   channel: string,
             *   message: string,
             *   created_at: string
             * } $row
             */
            $results[] = $this->mapRowToDTO($row);
        }

        return $results;
    }

    /**
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     * @return array<NotificationSummaryDTO>
     */
    public function findByDateRange(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM failed_notifications
            WHERE created_at BETWEEN :from AND :to
            ORDER BY created_at DESC'
        );
        $stmt->execute([
            ':from' => $from->format('Y-m-d H:i:s'),
            ':to' => $to->format('Y-m-d H:i:s'),
        ]);

        $results = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            if (! is_array($row)) {
                continue;
            }

            /**
             * @var array{
             *   id: int|string,
             *   channel: string,
             *   message: string,
             *   created_at: string
             * } $row
             */
            $results[] = $this->mapRowToDTO($row);
        }

        return $results;
    }

    /**
     * @param array{
     *   id: int|string,
     *   channel: string,
     *   message: string,
     *   created_at: string
     * } $row
     * @return NotificationSummaryDTO
     */
    private function mapRowToDTO(array $row): NotificationSummaryDTO
    {
        // Parse message to extract title and body if possible
        // Format used in FailedNotificationRepository: "Title: %s\n\nBody: %s"
        $message = (string)($row['message'] ?? '');
        $title = '';
        $body = $message;

        if (preg_match('/^Title: (.*?)\n\nBody: (.*)$/s', $message, $matches)) {
            $title = $matches[1];
            $body = $matches[2];
        }

        $createdAtStr = (string)($row['created_at'] ?? 'now');
        try {
            $createdAt = new DateTimeImmutable($createdAtStr);
        } catch (\Exception) {
            $createdAt = new DateTimeImmutable();
        }

        return new NotificationSummaryDTO(
            notificationId: (int)($row['id'] ?? 0),
            adminId: null, // Not available in failed_notifications
            channel: (string)($row['channel'] ?? 'unknown'),
            status: 'failed',
            title: $title,
            body: $body,
            createdAt: $createdAt,
            deliveredAt: null // Never delivered if in failed_notifications
        );
    }
}
