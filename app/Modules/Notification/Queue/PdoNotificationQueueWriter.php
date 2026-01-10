<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:15
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\Queue;

use App\Modules\Notification\Contract\NotificationQueueWriterInterface;
use App\Modules\Notification\Crypto\NotificationPayloadEncryptorInterface;
use App\Modules\Notification\Crypto\NotificationRecipientEncryptorInterface;
use App\Modules\Notification\DTO\NotificationDeliveryDTO;
use App\Modules\Notification\Enum\NotificationChannel;
use PDO;
use RuntimeException;

/**
 * PdoNotificationQueueWriter
 *
 * Persists notification delivery instructions
 * into notification_delivery_queue table.
 */
final readonly class PdoNotificationQueueWriter implements NotificationQueueWriterInterface
{
    public function __construct(
        private PDO $pdo,
        private NotificationRecipientEncryptorInterface $recipientEncryptor,
        private NotificationPayloadEncryptorInterface $payloadEncryptor,
    )
    {
    }

    public function enqueue(NotificationDeliveryDTO $delivery): void
    {
        $recipient = $this->recipientEncryptor->encrypt($delivery->recipient);
        $payload = $this->payloadEncryptor->encrypt($delivery->payload);

        $stmt = $this->pdo->prepare(
            <<<SQL
INSERT INTO notification_delivery_queue (
    intent_id,
    channel_type,
    entity_type,
    entity_id,
    recipient_encrypted,
    recipient_iv,
    recipient_tag,
    recipient_key_id,
    payload_encrypted,
    payload_iv,
    payload_tag,
    payload_key_id,
    channel_meta,
    status,
    priority,
    scheduled_at
) VALUES (
    :intent_id,
    :channel_type,
    :entity_type,
    :entity_id,
    :recipient_encrypted,
    :recipient_iv,
    :recipient_tag,
    :recipient_key_id,
    :payload_encrypted,
    :payload_iv,
    :payload_tag,
    :payload_key_id,
    :channel_meta,
    :status,
    :priority,
    :scheduled_at
)
SQL
        );

        $ok = $stmt->execute([
            'intent_id'    => $delivery->intentId,
            'channel_type' => $delivery->channel->value,
            'entity_type'  => $delivery->entityType,
            'entity_id'    => $delivery->entityId,

            'recipient_encrypted' => $recipient->ciphertext,
            'recipient_iv'        => $recipient->iv,
            'recipient_tag'       => $recipient->tag,
            'recipient_key_id'    => $recipient->keyId,

            'payload_encrypted' => $payload->ciphertext,
            'payload_iv'        => $payload->iv,
            'payload_tag'       => $payload->tag,
            'payload_key_id'    => $payload->keyId,

            'channel_meta' => json_encode($delivery->channelMeta, JSON_THROW_ON_ERROR),
            'status'       => 'pending',
            'priority'     => $delivery->priority,
            'scheduled_at' => $delivery->scheduledAt->format('Y-m-d H:i:s'),
        ]);

        if ($ok !== true) {
            throw new RuntimeException('Failed to enqueue notification delivery');
        }
    }
}
