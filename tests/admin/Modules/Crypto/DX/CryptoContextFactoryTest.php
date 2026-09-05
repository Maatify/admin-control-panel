<?php

declare(strict_types=1);

namespace Tests\Modules\Crypto\DX;

use Maatify\Crypto\DX\CryptoContextFactory;
use Maatify\Crypto\HKDF\HKDFService;
use Maatify\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use Maatify\Crypto\KeyRotation\KeyRotationService;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;
use Maatify\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;
use Maatify\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use Maatify\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm;
use Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use PHPUnit\Framework\TestCase;

final class CryptoContextFactoryTest extends TestCase
{
    public function testFactoryCreatesOpaqueCryptoService(): void
    {
        // Arrange real, minimal collaborators (no mocks)
        $keys = [
            new CryptoKeyDTO(
                'k1',
                random_bytes(32),
                KeyStatusEnum::ACTIVE,
                new \DateTimeImmutable()
            ),
        ];

        $keyRotation = new KeyRotationService(
            new InMemoryKeyProvider($keys),
            new StrictSingleActiveKeyPolicy()
        );

        $hkdf = new HKDFService();

        $registry = new ReversibleCryptoAlgorithmRegistry();
        $registry->register(new Aes256GcmAlgorithm());

        $factory = new CryptoContextFactory(
            $keyRotation,
            $hkdf,
            $registry
        );

        // Act
        $result = $factory->create('test:v1');

        // Assert (DX-level only)
        $this->assertIsObject($result);
    }
}

