<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:12
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\Reversible;

use Maatify\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm;
use Maatify\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;
use Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use Maatify\Crypto\Reversible\ReversibleCryptoService;
use PHPUnit\Framework\TestCase;

final class ReversibleCryptoServiceTest extends TestCase
{
    public function testServiceEncryptAndDecrypt(): void
    {
        $registry = new ReversibleCryptoAlgorithmRegistry();
        $registry->register(new Aes256GcmAlgorithm());

        $keys = [
            'v1' => random_bytes(32),
        ];

        $service = new ReversibleCryptoService(
            $registry,
            $keys,
            'v1',
            ReversibleCryptoAlgorithmEnum::AES_256_GCM
        );

        $encrypted = $service->encrypt('top-secret');

        $metadata = new ReversibleCryptoMetadataDTO(
            $encrypted['result']->iv,
            $encrypted['result']->tag
        );

        $plain = $service->decrypt(
            $encrypted['result']->cipher,
            $encrypted['key_id'],
            $encrypted['algorithm'],
            $metadata
        );

        $this->assertSame('top-secret', $plain);
    }

    public function testDecryptFailsWithWrongKeyId(): void
    {
        $this->expectException(\Throwable::class);

        $registry = new ReversibleCryptoAlgorithmRegistry();
        $registry->register(new Aes256GcmAlgorithm());

        $service = new ReversibleCryptoService(
            $registry,
            ['v1' => random_bytes(32)],
            'v1',
            ReversibleCryptoAlgorithmEnum::AES_256_GCM
        );

        $service->decrypt(
            'cipher',
            'invalid',
            ReversibleCryptoAlgorithmEnum::AES_256_GCM,
            new ReversibleCryptoMetadataDTO(null, null)
        );
    }
}
