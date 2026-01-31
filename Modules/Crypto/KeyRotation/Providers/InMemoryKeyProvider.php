<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:42
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\KeyRotation\Providers;

use Maatify\Crypto\KeyRotation\CryptoKeyInterface;
use Maatify\Crypto\KeyRotation\Exceptions\KeyNotFoundException;
use Maatify\Crypto\KeyRotation\Exceptions\MultipleActiveKeysException;
use Maatify\Crypto\KeyRotation\Exceptions\NoActiveKeyException;
use Maatify\Crypto\KeyRotation\KeyProviderInterface;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;

/**
 * InMemoryKeyProvider
 *
 * Reference in-memory implementation of KeyProviderInterface.
 *
 * Purpose:
 * - Tests
 * - Bootstrap
 * - Architecture verification
 *
 * Characteristics:
 * - Stores keys in memory (array)
 * - Fully enforces key status invariants
 * - No persistence
 */
final class InMemoryKeyProvider implements KeyProviderInterface
{
    /**
     * @var array<string, CryptoKeyInterface>
     */
    private array $keys = [];

    /**
     * @param   iterable<CryptoKeyInterface>  $keys
     */
    public function __construct(iterable $keys)
    {
        foreach ($keys as $key) {
            $this->keys[$key->id()] = $key;
        }

        $this->assertSingleActiveKey();
    }

    /**
     * {@inheritdoc}
     */
    public function all(): iterable
    {
        return array_values($this->keys);
    }

    /**
     * {@inheritdoc}
     */
    public function active(): CryptoKeyInterface
    {
        $active = null;

        foreach ($this->keys as $key) {
            if ($key->status() === KeyStatusEnum::ACTIVE) {
                if ($active !== null) {
                    throw new MultipleActiveKeysException('Multiple ACTIVE keys detected');
                }
                $active = $key;
            }
        }

        if ($active === null) {
            throw new NoActiveKeyException('No ACTIVE key found');
        }

        return $active;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $keyId): CryptoKeyInterface
    {
        return $this->keys[$keyId]
               ?? throw new KeyNotFoundException(sprintf('Key not found: %s', $keyId));
    }

    /**
     * {@inheritdoc}
     */
    public function promote(string $keyId): void
    {
        if (! isset($this->keys[$keyId])) {
            throw new KeyNotFoundException(sprintf('Key not found: %s', $keyId));
        }

        foreach ($this->keys as $id => $key) {
            if ($id === $keyId) {
                $this->keys[$id] = $key->withStatus(KeyStatusEnum::ACTIVE);
                continue;
            }

            if ($key->status() === KeyStatusEnum::ACTIVE) {
                $this->keys[$id] = $key->withStatus(KeyStatusEnum::INACTIVE);
            }
        }

        $this->assertSingleActiveKey();
    }

    /**
     * Enforce invariant: exactly ONE ACTIVE key.
     */
    private function assertSingleActiveKey(): void
    {
        $activeCount = 0;

        foreach ($this->keys as $key) {
            if ($key->status() === KeyStatusEnum::ACTIVE) {
                $activeCount++;
            }
        }

        if ($activeCount === 0) {
            throw new NoActiveKeyException('No ACTIVE key exists');
        }

        if ($activeCount > 1) {
            throw new MultipleActiveKeysException(
                sprintf('Multiple ACTIVE keys exist: %d', $activeCount)
            );
        }
    }
}
