<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\Reversible\Algorithms;

use Maatify\Crypto\Reversible\DTO\ReversibleCryptoEncryptionResultDTO;
use Maatify\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use Maatify\Crypto\Reversible\Exceptions\CryptoDecryptionFailedException;
use Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmInterface;
use RuntimeException;

/**
 * Aes256GcmAlgorithm
 *
 * Reference implementation of AES-256-GCM (AEAD).
 *
 * SECURITY NOTES:
 * - Uses a 96-bit (12 bytes) IV as recommended for GCM.
 * - Requires a 256-bit (32 bytes) raw binary key.
 * - Produces a 128-bit (16 bytes) authentication tag.
 *
 * This class performs reversible encryption and decryption.
 * It MUST NOT be used for hashing or one-way secrets.
 */
final class Aes256GcmAlgorithm implements ReversibleCryptoAlgorithmInterface
{
    /**
     * OpenSSL cipher identifier.
     *
     * Execution detail – MUST NOT be derived from enums.
     */
    private const CIPHER = 'aes-256-gcm';

    /** Recommended IV length for GCM (96-bit) */
    private const IV_LENGTH = 12;

    /** Authentication tag length (128-bit) */
    private const TAG_LENGTH = 16;

    public function algorithm(): ReversibleCryptoAlgorithmEnum
    {
        return ReversibleCryptoAlgorithmEnum::AES_256_GCM;
    }

    public function encrypt(string $plain, string $key): ReversibleCryptoEncryptionResultDTO
    {
        $this->assertKeyLength($key);

        $iv  = random_bytes(self::IV_LENGTH);
        $tag = '';

        $cipher = openssl_encrypt(
            $plain,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($cipher === false) {
            throw new RuntimeException('AES-256-GCM encryption failed');
        }

        if (strlen($tag) !== self::TAG_LENGTH) {
            throw new RuntimeException(
                sprintf(
                    'Invalid AES-256-GCM authentication tag length: expected %d bytes, got %d',
                    self::TAG_LENGTH,
                    strlen($tag)
                )
            );
        }

        return new ReversibleCryptoEncryptionResultDTO(
            $cipher,
            $iv,
            $tag
        );
    }

    public function decrypt(
        string $cipher,
        string $key,
        ReversibleCryptoMetadataDTO $metadata
    ): string {
        $this->assertKeyLength($key);

        if ($metadata->iv === null || $metadata->tag === null) {
            throw new CryptoDecryptionFailedException(
                'Missing IV or authentication tag for AES-256-GCM decryption'
            );
        }

        if (strlen($metadata->tag) !== self::TAG_LENGTH) {
            throw new CryptoDecryptionFailedException(
                sprintf(
                    'Invalid AES-256-GCM authentication tag length: expected %d bytes, got %d',
                    self::TAG_LENGTH,
                    strlen($metadata->tag)
                )
            );
        }

        $plain = openssl_decrypt(
            $cipher,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $metadata->iv,
            $metadata->tag
        );

        if ($plain === false) {
            throw new CryptoDecryptionFailedException(
                'AES-256-GCM authentication failed or data corrupted'
            );
        }

        return $plain;
    }

    /**
     * Ensures the provided key is exactly 256-bit (32 bytes).
     */
    private function assertKeyLength(string $key): void
    {
        if (strlen($key) !== 32) {
            throw new RuntimeException(
                sprintf(
                    'Invalid AES-256-GCM key length: expected 32 bytes, got %d',
                    strlen($key)
                )
            );
        }
    }
}
