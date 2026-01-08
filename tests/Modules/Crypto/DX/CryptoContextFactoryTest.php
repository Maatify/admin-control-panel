<?php

declare(strict_types=1);

namespace Tests\Modules\Crypto\DX;

use App\Modules\Crypto\DX\CryptoContextFactory;
use App\Modules\Crypto\HKDF\HKDFContext;
use App\Modules\Crypto\HKDF\HKDFService;
use App\Modules\Crypto\KeyRotation\KeyRotationService;
use App\Modules\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

        $this->factory = new CryptoContextFactory(
            $this->keyRotation,
            $this->hkdf,
            $this->registry
        );
    }

    public function testExportForCryptoIsCalledAndHKDFIsInvoked(): void
    {
        $contextString = 'test:v1';
        $activeKeyId = 'k1';
        $rootKeys = [
            'k1' => 'root-secret-1',
            'k2' => 'root-secret-2',
        ];

        // 1. Expect KeyRotation to provide keys
        $this->keyRotation->expects($this->once())
            ->method('exportForCrypto')
            ->willReturn([
                'keys' => $rootKeys,
                'active_key_id' => $activeKeyId,
            ]);

        // 2. Expect HKDF to be called for EACH root key
        // We expect 2 calls, matching the root keys
        $this->hkdf->expects($this->exactly(count($rootKeys)))
            ->method('deriveKey')
            ->willReturnCallback(function (string $rootKey, HKDFContext $ctx, int $len) use ($rootKeys) {
                // Verify length is 32 (AES-256)
                $this->assertSame(32, $len);
                // Verify it's one of our keys
                $this->assertContains($rootKey, $rootKeys);
                return 'derived-' . $rootKey;
            });

        // 3. Execute
        $service = $this->factory->create($contextString);

        // 4. Assert opaque object is returned
        $this->assertIsObject($service);

        // STRICT RULE: Do not check class type explicitly against forbidden class
    }
}
