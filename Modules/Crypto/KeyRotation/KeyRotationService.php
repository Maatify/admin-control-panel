<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:38
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\KeyRotation;

use Maatify\Crypto\KeyRotation\DTO\KeyRotationDecisionDTO;
use Maatify\Crypto\KeyRotation\DTO\KeyRotationStateDTO;
use Maatify\Crypto\KeyRotation\DTO\KeyRotationValidationResultDTO;
use Maatify\Crypto\KeyRotation\Exceptions\KeyRotationException;

/**
 * KeyRotationService
 *
 * Orchestrates key lifecycle decisions and exposes
 * crypto-ready outputs WITHOUT performing cryptography.
 *
 * Responsibilities:
 * - Validate key state
 * - Resolve active encryption key
 * - Resolve decryption keys by key_id
 * - Expose key material in a crypto-friendly format
 *
 * STRICT RULES:
 * - No OpenSSL
 * - No cipher knowledge
 * - No encryption / decryption
 * - Fail-closed on any invariant violation
 */
final readonly class KeyRotationService
{
    public function __construct(
        private KeyProviderInterface $provider,
        private KeyRotationPolicyInterface $policy
    )
    {
    }

    /**
     * Validate current key state against policy.
     */
    public function validate(): KeyRotationValidationResultDTO
    {
        try {
            $this->policy->validate($this->provider);

            return new KeyRotationValidationResultDTO(true);
        } catch (KeyRotationException $e) {
            return new KeyRotationValidationResultDTO(false, $e->getMessage());
        }
    }

    /**
     * Resolve active key for ENCRYPTION.
     *
     * @throws KeyRotationException
     */
    public function activeEncryptionKey(): CryptoKeyInterface
    {
        return $this->policy->encryptionKey($this->provider);
    }

    /**
     * Resolve key for DECRYPTION by key_id.
     *
     * @throws KeyRotationException
     */
    public function decryptionKey(string $keyId): CryptoKeyInterface
    {
        return $this->policy->decryptionKey($this->provider, $keyId);
    }

    /**
     * Export crypto-ready configuration for ReversibleCryptoService.
     *
     * @return array{
     *   keys: array<string,string>,
     *   active_key_id: string
     * }
     *
     * @throws KeyRotationException
     */
    public function exportForCrypto(): array
    {
        $active = $this->activeEncryptionKey();

        $keys = [];
        foreach ($this->provider->all() as $key) {
            if ($key->status()->canDecrypt()) {
                $keys[$key->id()] = $key->material();
            }
        }

        return [
            'keys'          => $keys,
            'active_key_id' => $active->id(),
        ];
    }

    /**
     * Snapshot current rotation state (for audit / debug).
     */
    public function snapshot(): KeyRotationStateDTO
    {
        $active = $this->provider->active();

        $inactive = [];
        $retired = [];

        foreach ($this->provider->all() as $key) {
            match ($key->status()) {
                KeyStatusEnum::INACTIVE => $inactive[] = $key,
                KeyStatusEnum::RETIRED => $retired[] = $key,
                default => null,
            };
        }

        return new KeyRotationStateDTO(
            activeKey   : $active,
            inactiveKeys: $inactive,
            retiredKeys : $retired
        );
    }

    /**
     * Promote a key to ACTIVE.
     *
     * NOTE:
     * - This method performs POLICY decision only.
     * - Actual persistence is delegated to the provider.
     */
    public function rotateTo(string $newActiveKeyId): KeyRotationDecisionDTO
    {
        $currentActive = $this->provider->active();

        if ($currentActive->id() === $newActiveKeyId) {
            return new KeyRotationDecisionDTO(
                newActiveKeyId     : $currentActive->id(),
                previousActiveKeyId: $currentActive->id(),
                rotationOccurred   : false
            );
        }

        $this->provider->promote($newActiveKeyId);

        return new KeyRotationDecisionDTO(
            newActiveKeyId     : $newActiveKeyId,
            previousActiveKeyId: $currentActive->id(),
            rotationOccurred   : true
        );
    }
}
