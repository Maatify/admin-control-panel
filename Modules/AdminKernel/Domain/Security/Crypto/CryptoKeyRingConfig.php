<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Crypto;

final readonly class CryptoKeyRingConfig
{
    /**
     * @param array<int, array{id: string, key: string}> $keys
     * @param string $activeKeyId
     */
    private function __construct(
        private array $keys,
        private string $activeKeyId
    ) {
    }

    /**
     * @param array<string, mixed> $env
     * @return self
     * @throws \Exception
     */
    public static function fromEnv(array $env): self
    {
        if (empty($env['CRYPTO_KEYS'])) {
            throw new \Exception('CRYPTO_KEYS is required and cannot be empty.');
        }

        $rawKeys = $env['CRYPTO_KEYS'];
        if (!is_string($rawKeys)) {
             throw new \Exception('CRYPTO_KEYS must be a string.');
        }

        /** @var mixed $keys */
        $keys = json_decode($rawKeys, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($keys) || empty($keys)) {
            throw new \Exception('CRYPTO_KEYS must be a non-empty JSON array');
        }

        // Validate structure
        $ids = [];
        foreach ($keys as $k) {
            if (!isset($k['id']) || !isset($k['key'])) {
                throw new \Exception('Invalid CRYPTO_KEYS structure. Each key must have "id" and "key".');
            }
            if (isset($ids[$k['id']])) {
                throw new \Exception('Duplicate key ID in CRYPTO_KEYS: ' . $k['id']);
            }
            $ids[$k['id']] = true;
        }

        $activeKeyId = $env['CRYPTO_ACTIVE_KEY_ID'] ?? null;
        if (empty($activeKeyId) || !is_string($activeKeyId)) {
            throw new \Exception('CRYPTO_ACTIVE_KEY_ID is required and must be a string.');
        }

        if (!isset($ids[$activeKeyId])) {
            throw new \Exception("CRYPTO_ACTIVE_KEY_ID '{$activeKeyId}' not found in CRYPTO_KEYS.");
        }

        /** @var array<int, array{id: string, key: string}> $keys */
        return new self($keys, $activeKeyId);
    }

    /**
     * @return array<int, array{id: string, key: string}>
     */
    public function keys(): array
    {
        return $this->keys;
    }

    public function activeKeyId(): string
    {
        return $this->activeKeyId;
    }
}
