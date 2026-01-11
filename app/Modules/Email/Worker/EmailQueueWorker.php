<?php

declare(strict_types=1);

namespace App\Modules\Email\Worker;

use App\Modules\Crypto\DX\CryptoProvider;
use App\Modules\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use App\Modules\Crypto\Reversible\Exceptions\CryptoDecryptionFailedException;
use App\Modules\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use App\Modules\Email\DTO\RenderedEmailDTO;
use App\Modules\Email\Exception\EmailTransportException;
use App\Modules\Email\Transport\EmailTransportInterface;
use JsonException;
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
        // 1. Transactional Selection & Locking (Option A)
        $this->pdo->beginTransaction();

        try {
            $sql = <<<'SQL'
                SELECT
                    id,
                    recipient_encrypted, recipient_iv, recipient_tag, recipient_key_id,
                    payload_encrypted, payload_iv, payload_tag, payload_key_id
                FROM email_queue
                WHERE status = 'pending'
                  AND scheduled_at <= NOW()
                ORDER BY priority ASC, id ASC
                LIMIT :limit
                FOR UPDATE
            SQL;

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                $this->pdo->commit();
                return;
            }

            // Extract IDs for bulk update
            $ids = array_column($rows, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // 2. Immediate State Transition
            $updateSql = <<<SQL
                UPDATE email_queue
                SET status = 'processing',
                    attempts = attempts + 1
                WHERE id IN ($placeholders)
            SQL;

            $updateStmt = $this->pdo->prepare($updateSql);
            $updateStmt->execute($ids);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            // In a real worker, we might log this critical failure, but instructions forbid logging frameworks.
            // We just exit/rethrow to stop the worker run.
            throw $e;
        }

        // 3. Process Locked Rows
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

        try {
            // 3. Decryption

            // Decrypt Recipient
            try {
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
            } catch (CryptoDecryptionFailedException $e) {
                throw new \RuntimeException('crypto_decryption_failed', 0, $e);
            }

            try {
                /** @var array{subject: string, htmlBody: string, templateKey: string, language: string} $payloadData */
                $payloadData = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new \RuntimeException('invalid_payload_format', 0, $e);
            }

            // 4. Sending
            $renderedEmail = new RenderedEmailDTO(
                $payloadData['subject'],
                $payloadData['htmlBody'],
                $payloadData['templateKey'],
                $payloadData['language']
            );

            try {
                $this->transport->send($recipient, $renderedEmail);
            } catch (EmailTransportException $e) {
                throw new \RuntimeException('smtp_transport_error', 0, $e);
            }

            // Success
            $completeStmt = $this->pdo->prepare(
                "UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = :id"
            );
            $completeStmt->execute(['id' => $id]);

        } catch (Throwable $e) {
            // 5. Failure Handling (Fix #2: Error Hygiene)
            $errorMsg = match ($e->getMessage()) {
                'crypto_decryption_failed' => 'crypto_decryption_failed',
                'invalid_payload_format' => 'invalid_payload_format',
                'smtp_transport_error' => 'smtp_transport_error',
                default => 'unexpected_worker_error',
            };

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
