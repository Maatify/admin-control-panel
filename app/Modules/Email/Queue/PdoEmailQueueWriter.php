<?php

declare(strict_types=1);

namespace App\Modules\Email\Queue;

use App\Modules\Crypto\DX\CryptoProvider;
use App\Modules\Crypto\Reversible\DTO\ReversibleCryptoEncryptionResultDTO;
use App\Modules\Email\Queue\DTO\EmailQueuePayloadDTO;
use App\Modules\Email\Exception\EmailQueueWriteException;
use DateTimeInterface;
use PDO;
use Throwable;

class PdoEmailQueueWriter implements EmailQueueWriterInterface
{
    private const RECIPIENT_CONTEXT = 'email:recipient:v1';
    private const PAYLOAD_CONTEXT = 'email:payload:v1';

    public function __construct(
        private readonly PDO $pdo,
        private readonly CryptoProvider $cryptoProvider
    ) {
    }

    public function enqueue(
        string $entityType,
        ?string $entityId,
        string $recipientEmail,
        EmailQueuePayloadDTO $payload,
        int $senderType,
        int $priority = 5,
        ?DateTimeInterface $scheduledAt = null
    ): void {
        try {
            // 1. Prepare data
            // Use DTO toArray() for the JSON payload as strictly required
            $serializedPayload = json_encode($payload->toArray(), JSON_THROW_ON_ERROR);

            // 2. Encrypt Recipient
            $recipientCrypto = $this->cryptoProvider->context(self::RECIPIENT_CONTEXT);
            /** @var array{result: ReversibleCryptoEncryptionResultDTO, key_id: string, algorithm: mixed} $recipientEncryptedData */
            $recipientEncryptedData = $recipientCrypto->encrypt($recipientEmail);

            $recipientResult = $recipientEncryptedData['result'];
            $recipientKeyId = $recipientEncryptedData['key_id'];

            // 3. Encrypt Payload
            $payloadCrypto = $this->cryptoProvider->context(self::PAYLOAD_CONTEXT);
            /** @var array{result: ReversibleCryptoEncryptionResultDTO, key_id: string, algorithm: mixed} $payloadEncryptedData */
            $payloadEncryptedData = $payloadCrypto->encrypt($serializedPayload);

            $payloadResult = $payloadEncryptedData['result'];
            $payloadKeyId = $payloadEncryptedData['key_id'];

            // 4. Insert into database
            $sql = <<<'SQL'
                INSERT INTO email_queue (
                    entity_type, entity_id,
                    recipient_encrypted, recipient_iv, recipient_tag, recipient_key_id,
                    payload_encrypted, payload_iv, payload_tag, payload_key_id,
                    template_key, language,
                    sender_type, priority,
                    status, attempts, last_error,
                    scheduled_at, created_at, updated_at
                ) VALUES (
                    :entity_type, :entity_id,
                    :recipient_encrypted, :recipient_iv, :recipient_tag, :recipient_key_id,
                    :payload_encrypted, :payload_iv, :payload_tag, :payload_key_id,
                    :template_key, :language,
                    :sender_type, :priority,
                    'pending', 0, '',
                    :scheduled_at, NOW(), NOW()
                )
            SQL;

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'recipient_encrypted' => $recipientResult->cipher,
                'recipient_iv' => $recipientResult->iv,
                'recipient_tag' => $recipientResult->tag,
                'recipient_key_id' => $recipientKeyId,
                'payload_encrypted' => $payloadResult->cipher,
                'payload_iv' => $payloadResult->iv,
                'payload_tag' => $payloadResult->tag,
                'payload_key_id' => $payloadKeyId,
                'template_key' => $payload->templateKey,
                'language' => $payload->language,
                'sender_type' => $senderType,
                'priority' => $priority,
                'scheduled_at' => $scheduledAt ? $scheduledAt->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            throw new EmailQueueWriteException('Failed to enqueue email', 0, $e);
        }
    }
}
