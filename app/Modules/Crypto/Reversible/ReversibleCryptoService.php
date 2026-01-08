<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 09:59
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Crypto\Reversible;

use App\Modules\Crypto\Reversible\DTO\ReversibleCryptoEncryptionResultDTO;
use App\Modules\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use App\Modules\Crypto\Reversible\Exceptions\CryptoAlgorithmNotSupportedException;
use App\Modules\Crypto\Reversible\Exceptions\CryptoKeyNotFoundException;
use App\Modules\Crypto\Reversible\Exceptions\CryptoDecryptionFailedException;
use App\Modules\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use Throwable;

/**
 * ReversibleCryptoService
 *
 * Orchestrates reversible (decryptable) symmetric cryptography.
 *
 * IMPORTANT:
 * - This service performs BOTH encryption and decryption.
 * - It MUST NOT be used for hashing or one-way secrets.
 * - It does NOT load keys from env or storage.
 * - It is stateless and safe for reuse.
 *
 * Responsibilities:
 * - Select active algorithm and key for encryption
 * - Route operations to the correct algorithm implementation
 * - Enforce fail-closed behavior
 */
final readonly class ReversibleCryptoService
{
    /**
     * @param   array<string,string>  $keys  Raw binary keys indexed by key_id
     */
    public function __construct(
        private ReversibleCryptoAlgorithmRegistry $registry,
        private array $keys,
        private string $activeKeyId,
        private ReversibleCryptoAlgorithmEnum $activeAlgorithm
    )
    {
        if (! isset($this->keys[$this->activeKeyId])) {
            throw new CryptoKeyNotFoundException(
                sprintf('Active crypto key not found: %s', $this->activeKeyId)
            );
        }
    }

    /**
     * Encrypt plaintext using the active algorithm and key.
     *
     * @return array{
     *   result: ReversibleCryptoEncryptionResultDTO,
     *   key_id: string,
     *   algorithm: ReversibleCryptoAlgorithmEnum
     * }
     * @throws Throwable
     */
    public function encrypt(string $plain): array
    {
        $algorithm = $this->getAlgorithm($this->activeAlgorithm);
        $key = $this->getKey($this->activeKeyId);

        $result = $algorithm->encrypt($plain, $key);

        return [
            'result'    => $result,
            'key_id'    => $this->activeKeyId,
            'algorithm' => $this->activeAlgorithm,
        ];
    }

    /**
     * Decrypt ciphertext using stored metadata.
     *
     * @throws CryptoDecryptionFailedException
     */
    public function decrypt(
        string $cipher,
        string $keyId,
        ReversibleCryptoAlgorithmEnum $algorithmEnum,
        ReversibleCryptoMetadataDTO $metadata
    ): string
    {
        $algorithm = $this->getAlgorithm($algorithmEnum);
        $key = $this->getKey($keyId);

        try {
            return $algorithm->decrypt($cipher, $key, $metadata);
        } catch (Throwable $e) {
            throw new CryptoDecryptionFailedException(
                'Reversible crypto decryption failed',
                previous: $e
            );
        }
    }

    /**
     * Resolve a crypto algorithm from the registry.
     */
    private function getAlgorithm(
        ReversibleCryptoAlgorithmEnum $algorithm
    ): ReversibleCryptoAlgorithmInterface
    {
        if (! $this->registry->has($algorithm)) {
            throw new CryptoAlgorithmNotSupportedException(
                sprintf('Crypto algorithm not registered: %s', $algorithm->value)
            );
        }

        return $this->registry->get($algorithm);
    }

    /**
     * Retrieve a raw encryption key by key_id.
     */
    private function getKey(string $keyId): string
    {
        return $this->keys[$keyId]
               ?? throw new CryptoKeyNotFoundException(
                sprintf('Crypto key not found: %s', $keyId)
            );
    }
}
