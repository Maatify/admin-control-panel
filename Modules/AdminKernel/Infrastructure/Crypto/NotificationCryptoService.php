<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-13
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Crypto;

use Maatify\AdminKernel\Application\Crypto\NotificationCryptoServiceInterface;
use Maatify\AdminKernel\Domain\DTO\Crypto\EncryptedPayloadDTO;
use Maatify\Crypto\Contract\CryptoContextProviderInterface;
use Maatify\Crypto\DX\CryptoProvider;
use Maatify\Crypto\Reversible\DTO\ReversibleCryptoEncryptionResultDTO;
use Maatify\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use RuntimeException;

/**
 * NotificationCryptoService
 *
 * Infrastructure-level wrapper around the canonical reversible crypto pipeline.
 *
 * IMPORTANT:
 * - NO new crypto logic.
 * - Delegates to CryptoProvider contexts only.
 * - Preserves current behavior (System B).
 */


final readonly class NotificationCryptoService implements NotificationCryptoServiceInterface
{
    public function __construct(
        private CryptoProvider $cryptoProvider,
        private CryptoContextProviderInterface $cryptoContextProvider,
    ) {
    }

    public function encryptRecipient(string $email): EncryptedPayloadDTO
    {

        $response = $this->cryptoProvider
            ->context($this->cryptoContextProvider->notificationEmailRecipient())
            ->encrypt($email);

        /** @var ReversibleCryptoEncryptionResultDTO $result */
        $result = $response['result'];

        return new EncryptedPayloadDTO(
            ciphertext: $result->cipher,
            iv: $result->iv ?? '',
            tag: $result->tag ?? '',
            keyId: $response['key_id']
        );
    }

    public function decryptRecipient(EncryptedPayloadDTO $encryptedRecipient): string
    {
        $metadata = new ReversibleCryptoMetadataDTO(
            iv: $encryptedRecipient->iv !== '' ? $encryptedRecipient->iv : null,
            tag: $encryptedRecipient->tag !== '' ? $encryptedRecipient->tag : null
        );

        return $this->cryptoProvider
            ->context($this->cryptoContextProvider->notificationEmailRecipient())
            ->decrypt(
                $encryptedRecipient->ciphertext,
                $encryptedRecipient->keyId,
                ReversibleCryptoAlgorithmEnum::AES_256_GCM,
                $metadata
            );
    }

    public function encryptPayload(array $payload): EncryptedPayloadDTO
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        $response = $this->cryptoProvider
            ->context($this->cryptoContextProvider->notificationEmailPayload())
            ->encrypt($json);

        /** @var ReversibleCryptoEncryptionResultDTO $result */
        $result = $response['result'];

        return new EncryptedPayloadDTO(
            ciphertext: $result->cipher,
            iv: $result->iv ?? '',
            tag: $result->tag ?? '',
            keyId: $response['key_id']
        );
    }

    public function decryptPayload(EncryptedPayloadDTO $encryptedPayload): array
    {
        $metadata = new ReversibleCryptoMetadataDTO(
            iv: $encryptedPayload->iv !== '' ? $encryptedPayload->iv : null,
            tag: $encryptedPayload->tag !== '' ? $encryptedPayload->tag : null
        );

        $json = $this->cryptoProvider
            ->context($this->cryptoContextProvider->notificationEmailPayload())
            ->decrypt(
                $encryptedPayload->ciphertext,
                $encryptedPayload->keyId,
                ReversibleCryptoAlgorithmEnum::AES_256_GCM,
                $metadata
            );

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('Decrypted email payload is not an array.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
