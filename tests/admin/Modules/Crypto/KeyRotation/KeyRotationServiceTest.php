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

namespace Tests\Modules\Crypto\KeyRotation;

use DateTimeImmutable;
use Maatify\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use Maatify\Crypto\KeyRotation\KeyRotationService;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;
use Maatify\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;
use Maatify\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use PHPUnit\Framework\TestCase;

final class KeyRotationServiceTest extends TestCase
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

    public function testExportForCrypto(): void
    {
        $provider = new InMemoryKeyProvider([
            $this->key('v1', KeyStatusEnum::ACTIVE),
            $this->key('v2', KeyStatusEnum::INACTIVE),
        ]);

        $service = new KeyRotationService(
            provider: $provider,
            policy  : new StrictSingleActiveKeyPolicy()
        );

        $export = $service->exportForCrypto();

        $this->assertSame('v1', $export['active_key_id']);
        $this->assertArrayHasKey('v1', $export['keys']);
        $this->assertArrayHasKey('v2', $export['keys']);
    }

    public function testRotateToNewKey(): void
    {
        $provider = new InMemoryKeyProvider([
            $this->key('v1', KeyStatusEnum::ACTIVE),
            $this->key('v2', KeyStatusEnum::INACTIVE),
        ]);

        $service = new KeyRotationService(
            provider: $provider,
            policy  : new StrictSingleActiveKeyPolicy()
        );

        $decision = $service->rotateTo('v2');

        $this->assertTrue($decision->rotationOccurred);
        $this->assertSame('v2', $provider->active()->id());
    }
}
