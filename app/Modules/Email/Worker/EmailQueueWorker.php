<?php

declare(strict_types=1);

namespace App\Modules\Email\Worker;

use App\Modules\Crypto\DX\CryptoProvider;
use App\Modules\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use App\Modules\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use App\Modules\Email\DTO\RenderedEmailDTO;
use App\Modules\Email\Transport\EmailTransportInterface;
use PDO;
use Throwable;

class EmailQueueWorker
{
    private const RECIPIENT_CONTEXT = 'email:recipient:v1';
    private const PAYLOAD_CONTEXT = 'email:payload:v1';

    public function __construct(
        private readonly PDO $pdo,
        private readonly CryptoProvider $cryptoProvider,
        private readonly EmailTransportInterface $transport
    ) {
    }

    public function processBatch(int $limit = 50): void
    {
        // 1. Select pending rows
        // We select more than limit to increase chance of finding unlocked rows if we were using SKIP LOCKED,
        // but here we use optimistic locking so limit is fine.
        $sql = <<<'SQL'
            SELECT
                id,
                recipient_encrypted, recipient_iv, recipient_tag, recipient_key_id,
                payload_encrypted, payload_iv, payload_tag, payload_key_id,
                attempts
            FROM email_queue
            WHERE status = 'pending'
              AND scheduled_at <= NOW()
            ORDER BY priority ASC, id ASC
            LIMIT :limit
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $this->processRow($row);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function processRow(array $row): void
    {
        $id = (int)$row['id'];

        // 2. Lock / State Transition
        // Optimistic locking: try to set to processing. If zero rows updated, someone else got it.
        $updateStmt = $this->pdo->prepare(
            "UPDATE email_queue SET status = 'processing', attempts = attempts + 1 WHERE id = :id AND status = 'pending'"
        );
        $updateStmt->execute(['id' => $id]);

        if ($updateStmt->rowCount() === 0) {
            return; // Already picked up by another worker
        }

        try {
            // 3. Decryption

            // Decrypt Recipient
            $recipientCrypto = $this->cryptoProvider->context(self::RECIPIENT_CONTEXT);
            $recipient = $recipientCrypto->decrypt(
                (string)$row['recipient_encrypted'],
                (string)$row['recipient_key_id'],
                ReversibleCryptoAlgorithmEnum::AES_256_GCM,
                new ReversibleCryptoMetadataDTO(
                    (string)$row['recipient_iv'],
                    (string)$row['recipient_tag']
                )
            );

            // Decrypt Payload
            $payloadCrypto = $this->cryptoProvider->context(self::PAYLOAD_CONTEXT);
            $payloadJson = $payloadCrypto->decrypt(
                (string)$row['payload_encrypted'],
                (string)$row['payload_key_id'],
                ReversibleCryptoAlgorithmEnum::AES_256_GCM,
                new ReversibleCryptoMetadataDTO(
                    (string)$row['payload_iv'],
                    (string)$row['payload_tag']
                )
            );

            /** @var array{subject: string, htmlBody: string, templateKey: string, language: string} $payloadData */
            $payloadData = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);

            // 4. Sending
            $renderedEmail = new RenderedEmailDTO(
                $payloadData['subject'],
                $payloadData['htmlBody'],
                $payloadData['templateKey'],
                $payloadData['language']
            );

            $this->transport->send($recipient, $renderedEmail);

            // Success
            $completeStmt = $this->pdo->prepare(
                "UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = :id"
            );
            $completeStmt->execute(['id' => $id]);

        } catch (Throwable $e) {
            // 5. Failure Handling
            $errorMsg = substr($e->getMessage(), 0, 255); // Truncate to fit if necessary, though TEXT fits more.
            // Assuming last_error is text or varchar. Prompt says "Write a short error message".

            $failStmt = $this->pdo->prepare(
                "UPDATE email_queue SET status = 'failed', last_error = :error WHERE id = :id"
            );
            $failStmt->execute([
                'error' => $errorMsg,
                'id' => $id
            ]);
        }
    }
}
