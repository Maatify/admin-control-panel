<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:35
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\KeyRotation\Policy;

use Maatify\Crypto\KeyRotation\CryptoKeyInterface;
use Maatify\Crypto\KeyRotation\Exceptions\DecryptionKeyNotAllowedException;
use Maatify\Crypto\KeyRotation\Exceptions\KeyNotFoundException;
use Maatify\Crypto\KeyRotation\Exceptions\MultipleActiveKeysException;
use Maatify\Crypto\KeyRotation\Exceptions\NoActiveKeyException;
use Maatify\Crypto\KeyRotation\KeyProviderInterface;
use Maatify\Crypto\KeyRotation\KeyRotationPolicyInterface;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;

/**
 * StrictSingleActiveKeyPolicy
 *
 * Enforces the invariant:
 * - Exactly ONE ACTIVE key must exist at any time.
 *
 * Rules:
 * - Encryption uses ACTIVE key only
 * - Decryption allows ACTIVE / INACTIVE / RETIRED
 *
 * Fail-closed:
 * - Any invariant violation throws an exception.
 */
final class StrictSingleActiveKeyPolicy implements KeyRotationPolicyInterface
{
    public function validate(KeyProviderInterface $provider): void
    {
        $activeCount = 0;

        foreach ($provider->all() as $key) {
            if ($key->status() === KeyStatusEnum::ACTIVE) {
                $activeCount++;
            }
        }

        if ($activeCount === 0) {
            throw new NoActiveKeyException('No ACTIVE key exists (invariant violation)');
        }

        if ($activeCount > 1) {
            throw new MultipleActiveKeysException(
                sprintf('Multiple ACTIVE keys exist: %d (invariant violation)', $activeCount)
            );
        }
    }

    public function encryptionKey(KeyProviderInterface $provider): CryptoKeyInterface
    {
        $this->validate($provider);

        $active = $provider->active();

        if ($active->status() !== KeyStatusEnum::ACTIVE) {
            // Provider is lying / broken → fail closed
            throw new NoActiveKeyException('Provider returned a non-ACTIVE key as active()');
        }

        return $active;
    }

    public function decryptionKey(KeyProviderInterface $provider, string $keyId): CryptoKeyInterface
    {
        try {
            $key = $provider->find($keyId);
        } catch (\Throwable $e) {
            throw new KeyNotFoundException(
                sprintf('Key not found: %s', $keyId),
                previous: $e
            );
        }

        if (! $key->status()->canDecrypt()) {
            throw new DecryptionKeyNotAllowedException(
                sprintf('Key not allowed for decryption: %s (status: %s)', $keyId, $key->status()->value)
            );
        }

        return $key;
    }
}
