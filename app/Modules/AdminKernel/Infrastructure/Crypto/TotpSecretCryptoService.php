<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-13 10:49
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Crypto;

use Maatify\AdminKernel\Application\Crypto\TotpSecretCryptoServiceInterface;
use Maatify\AdminKernel\Domain\DTO\Crypto\EncryptedPayloadDTO;
use Maatify\Crypto\Contract\CryptoContextProviderInterface;
use Maatify\Crypto\DX\CryptoProvider;
use Maatify\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;

/**
 * TotpSecretCryptoService
 *
 * Infrastructure adapter over ReversibleCryptoService.
 *
 * Responsibilities:
 * - Delegate encryption/decryption to CryptoProvider
 * - Map reversible crypto DTOs to application-level DTOs
 *
 * STATUS:
 * - Phase 1
 * - Wrapper / Adapter only
 */
final class TotpSecretCryptoService implements TotpSecretCryptoServiceInterface
{
    public function __construct(
        private CryptoProvider $cryptoProvider,
        private readonly CryptoContextProviderInterface $cryptoContextProvider,

    ) {
    }

    public function encryptTotpSeed(string $plainSeed): EncryptedPayloadDTO
    {
        $response = $this->cryptoProvider
            ->context($this->cryptoContextProvider->totpSeed())
            ->encrypt($plainSeed);

        /** @var \Maatify\Crypto\Reversible\DTO\ReversibleCryptoEncryptionResultDTO $result */
        $result = $response['result'];

        return new EncryptedPayloadDTO(
            ciphertext: $result->cipher,
            iv: $result->iv ?? '',
            tag: $result->tag ?? '',
            keyId: $response['key_id']
        );
    }

    public function decryptTotpSeed(EncryptedPayloadDTO $encryptedSeed): string
    {
        $metadata = new ReversibleCryptoMetadataDTO(
            iv: $encryptedSeed->iv,
            tag: $encryptedSeed->tag
        );

        return $this->cryptoProvider
            ->context($this->cryptoContextProvider->totpSeed())
            ->decrypt(
                $encryptedSeed->ciphertext,
                $encryptedSeed->keyId,
                ReversibleCryptoAlgorithmEnum::AES_256_GCM,
                $metadata
            );
    }
}
