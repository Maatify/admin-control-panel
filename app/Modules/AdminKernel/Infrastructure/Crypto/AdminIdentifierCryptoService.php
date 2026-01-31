<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-13 10:40
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Crypto;

use Maatify\AdminKernel\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use Maatify\AdminKernel\Domain\DTO\Crypto\EncryptedPayloadDTO;
use Maatify\AdminKernel\Domain\Security\CryptoContext;
use App\Modules\Crypto\DX\CryptoProvider;
use App\Modules\Crypto\Reversible\DTO\ReversibleCryptoEncryptionResultDTO;
use App\Modules\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use App\Modules\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use RuntimeException;

/**
 * AdminIdentifierCryptoService
 *
 * Canonical infrastructure adapter for Admin Identifier cryptography.
 *
 * Responsibilities:
 * - Encrypt / decrypt admin email identifiers
 * - Derive blind index for admin email lookups
 *
 * STATUS:
 * - Phase 1 (Wrapper only)
 * - No behavior change
 */
final class AdminIdentifierCryptoService implements AdminIdentifierCryptoServiceInterface
{
    private string $blindIndexPepper;

    public function __construct(
        private CryptoProvider $cryptoProvider,
        string $blindIndexPepper
    )
    {
        if ($blindIndexPepper === '') {
            throw new RuntimeException('Admin identifier blind index pepper must be configured.');
        }

        $this->blindIndexPepper = $blindIndexPepper;
    }

    public function encryptEmail(string $plainEmail): EncryptedPayloadDTO
    {
        $response = $this->cryptoProvider
            ->context(CryptoContext::IDENTIFIER_EMAIL_V1)
            ->encrypt($plainEmail);

        /** @var ReversibleCryptoEncryptionResultDTO $result */
        $result = $response['result'];

        return new EncryptedPayloadDTO(
            ciphertext: $result->cipher,
            iv        : $result->iv ?? '',
            tag       : $result->tag ?? '',
            keyId     : $response['key_id']
        );
    }

    public function decryptEmail(EncryptedPayloadDTO $encryptedIdentifier): string
    {
        $metadata = new ReversibleCryptoMetadataDTO(
            iv : $encryptedIdentifier->iv !== '' ? $encryptedIdentifier->iv : null,
            tag: $encryptedIdentifier->tag !== '' ? $encryptedIdentifier->tag : null
        );

        return $this->cryptoProvider
            ->context(CryptoContext::IDENTIFIER_EMAIL_V1)
            ->decrypt(
                $encryptedIdentifier->ciphertext,
                $encryptedIdentifier->keyId,
                ReversibleCryptoAlgorithmEnum::AES_256_GCM,
                $metadata
            );
    }

    /**
     * Derive a stable blind index for admin email.
     *
     * Implementation:
     * - HMAC-SHA256
     * - Dedicated pepper (NOT password pepper)
     * - Stable output for indexed lookups
     */
    public function deriveEmailBlindIndex(string $plainEmail): string
    {
        return hash_hmac('sha256', $plainEmail, $this->blindIndexPepper);
    }
}

