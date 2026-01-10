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

use App\Modules\Notification\Contract\NotificationSenderInterface;
use App\Modules\Notification\Crypto\NotificationDecryptorInterface;
use App\Modules\Notification\Enum\NotificationChannel;
use PDO;
use RuntimeException;

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
    /**
     * @param   NotificationSenderInterface[]  $senders
     */
    public function __construct(
        private PDO $pdo,
        private array $senders,
        private NotificationDecryptorInterface $decryptor,
    )
    {
    }

    public function run(int $limit = 50): void
    {
        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare(
            <<<SQL
SELECT *
FROM notification_delivery_queue
WHERE status = 'pending'
  AND scheduled_at <= NOW()
ORDER BY priority ASC, scheduled_at ASC
LIMIT :limit
FOR UPDATE SKIP LOCKED
SQL
        );

        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $this->processRow($row);
        }

        $this->pdo->commit();
    }

    private function processRow(array $row): void
    {
        // Skeleton only:
        // 1) Mark as processing
        // 2) Decrypt recipient & payload
        // 3) Resolve sender by channel
        // 4) Send
        // 5) Update status (sent/failed/skipped)
    }

    private function resolveSender(NotificationChannel $channel): NotificationSenderInterface
    {
        foreach ($this->senders as $sender) {
            if ($sender->supports($channel)) {
                return $sender;
            }
        }

        throw new RuntimeException('No sender supports channel: ' . $channel->value);
    }
}
