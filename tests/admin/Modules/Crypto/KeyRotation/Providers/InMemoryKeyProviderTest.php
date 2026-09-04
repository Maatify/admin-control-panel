<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:45
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\KeyRotation\Providers;

use DateTimeImmutable;
use Maatify\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use Maatify\Crypto\KeyRotation\Exceptions\KeyNotFoundException;
use Maatify\Crypto\KeyRotation\Exceptions\MultipleActiveKeysException;
use Maatify\Crypto\KeyRotation\Exceptions\NoActiveKeyException;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;
use Maatify\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use PHPUnit\Framework\TestCase;

final class InMemoryKeyProviderTest extends TestCase
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

    public function testActiveKeyIsResolved(): void
    {
        $provider = new InMemoryKeyProvider([
            $this->key('v1', KeyStatusEnum::ACTIVE),
            $this->key('v2', KeyStatusEnum::INACTIVE),
        ]);

        $this->assertSame('v1', $provider->active()->id());
    }

    public function testNoActiveKeyFails(): void
    {
        $this->expectException(NoActiveKeyException::class);

        new InMemoryKeyProvider([
            $this->key('v1', KeyStatusEnum::INACTIVE),
        ]);
    }

    public function testMultipleActiveKeysFails(): void
    {
        $this->expectException(MultipleActiveKeysException::class);

        new InMemoryKeyProvider([
            $this->key('v1', KeyStatusEnum::ACTIVE),
            $this->key('v2', KeyStatusEnum::ACTIVE),
        ]);
    }

    public function testPromoteChangesActiveKey(): void
    {
        $provider = new InMemoryKeyProvider([
            $this->key('v1', KeyStatusEnum::ACTIVE),
            $this->key('v2', KeyStatusEnum::INACTIVE),
        ]);

        $provider->promote('v2');

        $this->assertSame('v2', $provider->active()->id());
    }

    public function testPromoteUnknownKeyFails(): void
    {
        $this->expectException(KeyNotFoundException::class);

        $provider = new InMemoryKeyProvider([
            $this->key('v1', KeyStatusEnum::ACTIVE),
        ]);

        $provider->promote('invalid');
    }
}
