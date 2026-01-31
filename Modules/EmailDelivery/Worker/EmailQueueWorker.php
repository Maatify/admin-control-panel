<?php

declare(strict_types=1);

namespace Maatify\EmailDelivery\Worker;

use JsonException;
use Maatify\Crypto\Contract\CryptoContextProviderInterface;
use Maatify\Crypto\DX\CryptoProvider;
use Maatify\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use Maatify\Crypto\Reversible\Exceptions\CryptoDecryptionFailedException;
use Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use Maatify\EmailDelivery\DTO\GenericEmailPayload;
use Maatify\EmailDelivery\Exception\EmailRenderException;
use Maatify\EmailDelivery\Exception\EmailTransportException;
use Maatify\EmailDelivery\Renderer\EmailRendererInterface;
use Maatify\EmailDelivery\Transport\EmailTransportInterface;
use PDO;
use Throwable;

/**
 * @phpstan-type EmailQueueRow array{
 *   id: int|string,
 *   recipient_encrypted: string,
 *   recipient_iv: string,
 *   recipient_tag: string,
 *   recipient_key_id: string,
 *   payload_encrypted: string,
 *   payload_iv: string,
 *   payload_tag: string,
 *   payload_key_id: string
 * }
 */
final readonly class EmailQueueWorker
{
    public function __construct(
        private PDO $pdo,
        private CryptoProvider $cryptoProvider,
        private EmailRendererInterface $renderer,
        private EmailTransportInterface $transport,
        private CryptoContextProviderInterface $cryptoContextProvider
    ) {
    }

    public function processBatch(int $limit = 50): void
    {
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

            /** @var list<EmailQueueRow> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($rows === []) {
                $this->pdo->commit();
                return;
            }

            $ids = array_map(
                static fn (array $row): int => (int) $row['id'],
                $rows
            );

            $placeholders = implode(',', array_fill(0, count($ids), '?'));

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
            throw $e;
        }

        foreach ($rows as $row) {
            $this->processRow($row);
        }
    }

    /**
     * @param EmailQueueRow $row
     */
    private function processRow(array $row): void
    {
        $id = (int) $row['id'];

        try {
            // Decrypt recipient
            try {
                $recipientCrypto = $this->cryptoProvider->context($this->cryptoContextProvider->emailQueueRecipient());

                $recipient = $recipientCrypto->decrypt(
                    $row['recipient_encrypted'],
                    $row['recipient_key_id'],
                    ReversibleCryptoAlgorithmEnum::AES_256_GCM,
                    new ReversibleCryptoMetadataDTO(
                        $row['recipient_iv'],
                        $row['recipient_tag']
                    )
                );

                // Decrypt payload
                $payloadCrypto = $this->cryptoProvider->context($this->cryptoContextProvider->emailQueuePayload());

                $payloadJson = $payloadCrypto->decrypt(
                    $row['payload_encrypted'],
                    $row['payload_key_id'],
                    ReversibleCryptoAlgorithmEnum::AES_256_GCM,
                    new ReversibleCryptoMetadataDTO(
                        $row['payload_iv'],
                        $row['payload_tag']
                    )
                );
            } catch (CryptoDecryptionFailedException $e) {
                throw new \RuntimeException('crypto_decryption_failed', 0, $e);
            }

            try {
                /** @var array{context: array<string, mixed>, templateKey: string, language: string} $payloadData */
                $payloadData = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new \RuntimeException('invalid_payload_format', 0, $e);
            }

            // Render
            try {
                $payload = new GenericEmailPayload($payloadData['context']);

                $renderedEmail = $this->renderer->render(
                    $payloadData['templateKey'],
                    $payloadData['language'],
                    $payload
                );
            } catch (EmailRenderException $e) {
                throw new \RuntimeException('email_render_failed', 0, $e);
            }

            // Send
            try {
                $this->transport->send($recipient, $renderedEmail);
            } catch (EmailTransportException $e) {
                throw new \RuntimeException('smtp_transport_error', 0, $e);
            }

            $completeStmt = $this->pdo->prepare(
                "UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = :id"
            );
            $completeStmt->execute(['id' => $id]);

        } catch (Throwable $e) {
            $errorMsg = match ($e->getMessage()) {
                'crypto_decryption_failed' => 'crypto_decryption_failed',
                'invalid_payload_format'   => 'invalid_payload_format',
                'email_render_failed'      => 'email_render_failed',
                'smtp_transport_error'     => 'smtp_transport_error',
                default                    => 'unexpected_worker_error',
            };

            $failStmt = $this->pdo->prepare(
                "UPDATE email_queue SET status = 'failed', last_error = :error WHERE id = :id"
            );
            $failStmt->execute([
                'error' => $errorMsg,
                'id'    => $id,
            ]);
        }
    }
}

