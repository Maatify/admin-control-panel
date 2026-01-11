<?php

declare(strict_types=1);

namespace App\Modules\Notification\Queue;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;
use App\Modules\Crypto\DX\CryptoProvider;
use App\Modules\Crypto\Reversible\DTO\ReversibleCryptoEncryptionResultDTO;
use App\Modules\Notification\Exception\NotificationQueueWriteException;
use PDO;
use Throwable;

class PdoNotificationQueueWriter implements NotificationQueueWriterInterface
{
    private const RECIPIENT_CONTEXT = 'notification:recipient:v1';
    private const PAYLOAD_CONTEXT = 'notification:payload:v1';

    public function __construct(
        private readonly PDO $pdo,
        private readonly CryptoProvider $cryptoProvider
    ) {
    }

    public function enqueue(NotificationDeliveryDTO $dto): void
    {
        try {
            // 1. Serialize payload
            $serializedPayload = json_encode($dto->payload, JSON_THROW_ON_ERROR);
            $serializedMeta = json_encode($dto->channelMeta, JSON_THROW_ON_ERROR);

            // 2. Encrypt Recipient
            $recipientCrypto = $this->cryptoProvider->context(self::RECIPIENT_CONTEXT);
            /** @var array{result: ReversibleCryptoEncryptionResultDTO, key_id: string, algorithm: mixed} $recipientEncryptedData */
            $recipientEncryptedData = $recipientCrypto->encrypt($dto->recipient);
            $recipientResult = $recipientEncryptedData['result'];
            $recipientKeyId = $recipientEncryptedData['key_id'];

            // 3. Encrypt Payload
            $payloadCrypto = $this->cryptoProvider->context(self::PAYLOAD_CONTEXT);
            /** @var array{result: ReversibleCryptoEncryptionResultDTO, key_id: string, algorithm: mixed} $payloadEncryptedData */
            $payloadEncryptedData = $payloadCrypto->encrypt($serializedPayload);
            $payloadResult = $payloadEncryptedData['result'];
            $payloadKeyId = $payloadEncryptedData['key_id'];

            // 4. Insert
            $sql = <<<'SQL'
                INSERT INTO notification_delivery_queue (
                    intent_id,
                    channel_type,
                    entity_type,
                    entity_id,
                    recipient_encrypted, recipient_iv, recipient_tag, recipient_key_id,
                    payload_encrypted, payload_iv, payload_tag, payload_key_id,
                    channel_meta,
                    status,
                    priority,
                    scheduled_at,
                    created_at,
                    updated_at
                ) VALUES (
                    :intent_id,
                    :channel_type,
                    :entity_type,
                    :entity_id,
                    :recipient_encrypted, :recipient_iv, :recipient_tag, :recipient_key_id,
                    :payload_encrypted, :payload_iv, :payload_tag, :payload_key_id,
                    :channel_meta,
                    'pending',
                    :priority,
                    :scheduled_at,
                    NOW(),
                    NOW()
                )
            SQL;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'intent_id' => $dto->intentId,
                'channel_type' => $dto->channel,
                'entity_type' => $dto->entityType,
                'entity_id' => $dto->entityId,
                'recipient_encrypted' => $recipientResult->cipher,
                'recipient_iv' => $recipientResult->iv,
                'recipient_tag' => $recipientResult->tag,
                'recipient_key_id' => $recipientKeyId,
                'payload_encrypted' => $payloadResult->cipher,
                'payload_iv' => $payloadResult->iv,
                'payload_tag' => $payloadResult->tag,
                'payload_key_id' => $payloadKeyId,
                'channel_meta' => $serializedMeta,
                'priority' => $dto->priority,
                'scheduled_at' => $dto->scheduledAt->format('Y-m-d H:i:s'),
            ]);

        } catch (Throwable $e) {
            throw new NotificationQueueWriteException('Failed to enqueue notification', 0, $e);
        }
    }
}
