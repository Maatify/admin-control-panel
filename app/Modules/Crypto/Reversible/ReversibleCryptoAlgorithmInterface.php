<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 09:57
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Crypto\Reversible;

use App\Modules\Crypto\Reversible\DTO\ReversibleCryptoEncryptionResultDTO;
use App\Modules\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use Throwable;

/**
 * ReversibleCryptoAlgorithmInterface
 *
 * Contract for reversible (decryptable) symmetric cryptography algorithms.
 *
 * IMPORTANT:
 * - This interface MUST NOT be used for hashing or one-way secrets.
 * - Implementations MUST support both encryption AND decryption.
 * - Any failure during encryption or decryption MUST throw an exception (fail-closed).
 *
 * This interface does NOT:
 * - Load keys
 * - Manage key rotation
 * - Access environment variables
 * - Interact with storage or databases
 */
interface ReversibleCryptoAlgorithmInterface
{
    /**
     * Returns the algorithm identifier.
     *
     * This value MUST match a case value from ReversibleCryptoAlgorithmEnum.
     */
    public function algorithm(): ReversibleCryptoAlgorithmEnum;

    /**
     * Encrypts plaintext using the provided raw key.
     *
     * @param   string  $plain  Plaintext data to encrypt
     * @param   string  $key    Raw binary encryption key
     *
     * @throws Throwable If encryption fails for any reason
     */
    public function encrypt(string $plain, string $key): ReversibleCryptoEncryptionResultDTO;

    /**
     * Decrypts ciphertext using the provided raw key and metadata.
     *
     * @param   string                       $cipher    Encrypted binary data
     * @param   string                       $key       Raw binary encryption key
     * @param   ReversibleCryptoMetadataDTO  $metadata  IV / Tag metadata required for decryption
     *
     * @return string The original plaintext
     *
     * @throws Throwable If decryption fails or authentication is invalid
     */
    public function decrypt(
        string $cipher,
        string $key,
        ReversibleCryptoMetadataDTO $metadata
    ): string;
}
