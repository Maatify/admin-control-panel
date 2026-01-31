<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:46
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\KeyRotation\Policy;

use DateTimeImmutable;
use Maatify\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use Maatify\Crypto\KeyRotation\Exceptions\KeyNotFoundException;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;
use Maatify\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;
use Maatify\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use PHPUnit\Framework\TestCase;

final class StrictSingleActiveKeyPolicyTest extends TestCase
{
    private function key(string $id, KeyStatusEnum $status): CryptoKeyDTO
    {
        return new CryptoKeyDTO(
            id       : $id,
            material : random_bytes(32),
            status   : $status,
            createdAt: new DateTimeImmutable()
        );
    }

    public function testEncryptionUsesActiveKeyOnly(): void
    {
        $provider = new InMemoryKeyProvider([
            $this->key('v1', KeyStatusEnum::ACTIVE),
            $this->key('v2', KeyStatusEnum::INACTIVE),
        ]);

        $policy = new StrictSingleActiveKeyPolicy();

        $this->assertSame(
            'v1',
            $policy->encryptionKey($provider)->id()
        );
    }

    public function testDecryptionAllowsInactiveKey(): void
    {
        $provider = new InMemoryKeyProvider([
            $this->key('v1', KeyStatusEnum::ACTIVE),
            $this->key('v2', KeyStatusEnum::INACTIVE),
        ]);

        $policy = new StrictSingleActiveKeyPolicy();

        $this->assertSame(
            'v2',
            $policy->decryptionKey($provider, 'v2')->id()
        );
    }

    public function testDecryptionFailsForUnknownKey(): void
    {
        $this->expectException(KeyNotFoundException::class);

        $provider = new InMemoryKeyProvider([
            $this->key('v1', KeyStatusEnum::ACTIVE),
        ]);

        $policy = new StrictSingleActiveKeyPolicy();

        $policy->decryptionKey($provider, 'unknown');
    }

}
