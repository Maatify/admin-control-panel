<?php

declare(strict_types=1);

namespace Tests\Modules\Crypto\DX;

use App\Modules\Crypto\DX\CryptoContextFactory;
use App\Modules\Crypto\HKDF\HKDFContext;
use App\Modules\Crypto\HKDF\HKDFService;
use App\Modules\Crypto\KeyRotation\KeyRotationService;
use App\Modules\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use App\Modules\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use App\Modules\Crypto\Reversible\ReversibleCryptoService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CryptoContextFactoryTest extends TestCase
{
    private CryptoContextFactory $factory;
    private MockObject&KeyRotationService $keyRotation;
    private MockObject&HKDFService $hkdf;
    private MockObject&ReversibleCryptoAlgorithmRegistry $registry;

    protected function setUp(): void
    {
        $this->keyRotation = $this->createMock(KeyRotationService::class);
        $this->hkdf = $this->createMock(HKDFService::class);
        $this->registry = $this->createMock(ReversibleCryptoAlgorithmRegistry::class);

        // Registry should verify 'AES_256_GCM' is present as default construction requirement
        // Actually ReversibleCryptoService constructor calls $registry->has() implicitly when constructed?
        // Wait, ReversibleCryptoService constructor checks $this->keys[$activeKeyId] exists.
        // It does NOT check registry in constructor. It checks registry in encrypt/decrypt/getAlgorithm.
        // So we can mock registry loosely.

        $this->factory = new CryptoContextFactory(
            $this->keyRotation,
            $this->hkdf,
            $this->registry
        );
    }

    public function testExportForCryptoIsCalledAndKeysAreDerived(): void
    {
        $contextString = 'test:v1';
        $rootKeys = [
            'key1' => 'root_secret_1',
            'key2' => 'root_secret_2',
        ];
        $activeKeyId = 'key1';

        $this->keyRotation->expects($this->once())
            ->method('exportForCrypto')
            ->willReturn([
                'keys' => $rootKeys,
                'active_key_id' => $activeKeyId,
            ]);

        // HKDF should be called exactly twice, once for each root key
        $matcher = $this->exactly(2);
        $this->hkdf->expects($matcher)
            ->method('deriveKey')
            ->willReturnCallback(function (string $rootKey, HKDFContext $context, int $length) use ($matcher, $contextString, $rootKeys) {
                // Verify context string matches
                $this->assertSame($contextString, $context->value());
                // Verify length is 32 (AES-256)
                $this->assertSame(32, $length);

                // Return a fake derived key
                return 'derived_' . $rootKey;
            });

        $service = $this->factory->create($contextString);

        $this->assertInstanceOf(ReversibleCryptoService::class, $service);
    }
}
