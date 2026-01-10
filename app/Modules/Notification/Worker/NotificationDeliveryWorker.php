<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

use App\Modules\Notification\Contract\NotificationSenderRegistryInterface;
use App\Modules\Notification\Crypto\NotificationDecryptorInterface;
use App\Modules\Notification\Crypto\NotificationEncryptedValueDTO;
use App\Modules\Notification\Enum\NotificationChannel;
use PDO;
use Throwable;

/**
 * NotificationDeliveryWorker
 *
 * CLI worker that processes notification_delivery_queue.
 *
 * NOTE:
 * - No sender implementations here
 * - No decryption logic here (delegated)
 * - Lifecycle only
 */
final class NotificationDeliveryWorker implements NotificationDeliveryWorkerInterface
{
    public function __construct(
        private PDO $pdo,
        private NotificationSenderRegistryInterface $senderRegistry,
        private NotificationDecryptorInterface $decryptor,
    ) {}

    public function run(int $limit = 50): void
    {
        $this->pdo->beginTransaction();

//        $stmt = $this->pdo->prepare(
//            <<<SQL
//SELECT *
//FROM notification_delivery_queue
//WHERE status = 'pending'
//  AND scheduled_at <= NOW()
//ORDER BY priority ASC, scheduled_at ASC
//LIMIT :limit
//FOR UPDATE SKIP LOCKED
//SQL
//        );

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $sql = <<<SQL
SELECT *
FROM notification_delivery_queue
WHERE status = 'pending'
  AND scheduled_at <= NOW()
ORDER BY priority ASC, scheduled_at ASC
LIMIT :limit
SQL;

        if ($driver !== 'sqlite') {
            $sql .= ' FOR UPDATE SKIP LOCKED';
        }

        $stmt = $this->pdo->prepare($sql);


        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            /** @var array{
             *   id: int,
             *   status: string,
             *   channel_type: string,
             *   entity_id: string|int,
             *   intent_id: string,
             *   notification_type?: string,
             *   recipient_encrypted: string,
             *   recipient_iv: string,
             *   recipient_tag: string,
             *   recipient_key_id: string,
             *   payload_encrypted: string,
             *   payload_iv: string,
             *   payload_tag: string,
             *   payload_key_id: string,
             *   channel_meta?: string
             * } $row */
            $this->processRow($row);
        }

        $this->pdo->commit();
    }

    /**
     * @param array{
     *   id: int,
     *   status: string,
     *   channel_type: string,
     *   entity_id: string|int,
     *   intent_id: string,
     *   notification_type?: string,
     *   recipient_encrypted: string,
     *   recipient_iv: string,
     *   recipient_tag: string,
     *   recipient_key_id: string,
     *   payload_encrypted: string,
     *   payload_iv: string,
     *   payload_tag: string,
     *   payload_key_id: string,
     *   channel_meta?: string
     * } $row
     */
    private function processRow(array $row): void
    {
        try {
            // 0) Guard
            if ($row['status'] !== 'pending') {
                return;
            }

            // 1) Mark as processing
            $update = $this->pdo->prepare(
                <<<SQL
UPDATE notification_delivery_queue
SET status = 'processing',
    attempts = attempts + 1,
    updated_at = NOW()
WHERE id = :id AND status = 'pending'
SQL
            );

            $update->execute(['id' => $row['id']]);

            if ($update->rowCount() === 0) {
                return;
            }

            // 2) Build encrypted DTOs
            $recipientEncrypted = new NotificationEncryptedValueDTO(
                $row['recipient_encrypted'],
                $row['recipient_iv'],
                $row['recipient_tag'],
                $row['recipient_key_id']
            );

            $payloadEncrypted = new NotificationEncryptedValueDTO(
                $row['payload_encrypted'],
                $row['payload_iv'],
                $row['payload_tag'],
                $row['payload_key_id']
            );

            // 3) Decrypt
            try {
                $recipient = $this->decryptor->decrypt($recipientEncrypted);
                $payload   = $this->decryptor->decrypt($payloadEncrypted);
            } catch (Throwable) {
                $this->failQueue($row['id'], 'decrypt_failed');
                $this->writeNotificationHistory($row, 'failed');
                return;
            }

            // 4) Resolve sender
            try {
                $channel = NotificationChannel::from($row['channel_type']);
                $sender  = $this->senderRegistry->resolve($channel);
            } catch (Throwable) {
                $this->skipQueue($row['id'], 'no_sender_for_channel');
                $this->writeNotificationHistory($row, 'skipped');
                return;
            }

            // 5) Decode channel meta
            try {
                $meta = json_decode(
                    $row['channel_meta'] ?? '{}',
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                if (!is_array($meta)) {
                    throw new \RuntimeException('Invalid channel meta');
                }
            } catch (Throwable) {
                $this->failQueue($row['id'], 'invalid_channel_meta');
                $this->writeNotificationHistory($row, 'failed');
                return;
            }

            // 6) Send
            try {
                /** @var array<string, mixed> $meta */
                $result = $sender->send($recipient, $payload, $meta);
            } catch (Throwable) {
                $this->failQueue($row['id'], 'sender_exception');
                $this->writeNotificationHistory($row, 'failed');
                return;
            }

            // 7) Interpret result
            if ($result->status === 'sent') {
                $stmt = $this->pdo->prepare(
                    <<<SQL
UPDATE notification_delivery_queue
SET status = 'sent',
    sent_at = NOW(),
    last_error = '',
    updated_at = NOW()
WHERE id = :id
SQL
                );
                $stmt->execute(['id' => $row['id']]);
            } elseif ($result->status === 'skipped') {
                $error = $result->errorCode ?? 'unknown_error';
                $this->skipQueue($row['id'], $error);
            } else {
                $error = $result->errorCode ?? 'unknown_error';
                $this->failQueue($row['id'], $error);
            }

            // 8) Write UX history
            $this->writeNotificationHistory($row, $result->status);

        } catch (Throwable) {
            // 9) Safety net
            $this->failQueue($row['id'], 'worker_exception');
            $this->writeNotificationHistory($row, 'failed');
        }
    }

    private function failQueue(int $id, string $error): void
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
UPDATE notification_delivery_queue
SET status = 'failed',
    last_error = :error,
    updated_at = NOW()
WHERE id = :id
SQL
        );

        $stmt->execute([
            'id'    => $id,
            'error' => $error,
        ]);
    }

    private function skipQueue(int $id, string $error): void
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
UPDATE notification_delivery_queue
SET status = 'skipped',
    last_error = :error,
    updated_at = NOW()
WHERE id = :id
SQL
        );

        $stmt->execute([
            'id'    => $id,
            'error' => $error,
        ]);
    }

    /**
     * @param array{
     *   entity_id: string|int,
     *   notification_type?: string,
     *   channel_type: string,
     *   intent_id: string
     * } $row
     */
    private function writeNotificationHistory(array $row, string $status): void
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
INSERT INTO admin_notifications (
    admin_id,
    notification_type,
    channel_type,
    intent_id,
    created_at,
    read_at
) VALUES (
    :admin_id,
    :notification_type,
    :channel_type,
    :intent_id,
    NOW(),
    NULL
)
SQL
        );

        $stmt->execute([
            'admin_id'           => $row['entity_id'],
            'notification_type' => $row['notification_type'] ?? '',
            'channel_type'      => $row['channel_type'],
            'intent_id'         => $row['intent_id'],
        ]);
    }
}
