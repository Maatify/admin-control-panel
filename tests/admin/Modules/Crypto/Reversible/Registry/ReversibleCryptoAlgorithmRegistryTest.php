<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:11
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\Reversible\Registry;

use Maatify\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm;
use Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use PHPUnit\Framework\TestCase;

final class ReversibleCryptoAlgorithmRegistryTest extends TestCase
{
    public function testRegisterAndRetrieveAlgorithm(): void
    {
        $registry = new ReversibleCryptoAlgorithmRegistry();
        $algorithm = new Aes256GcmAlgorithm();

        $registry->register($algorithm);

        $resolved = $registry->get(ReversibleCryptoAlgorithmEnum::AES_256_GCM);

        $this->assertSame($algorithm, $resolved);
    }

    public function testDuplicateRegistrationFails(): void
    {
        $this->expectException(\Throwable::class);

        $registry = new ReversibleCryptoAlgorithmRegistry();
        $algorithm = new Aes256GcmAlgorithm();

        $registry->register($algorithm);
        $registry->register($algorithm);
    }

    public function testUnsupportedAlgorithmFails(): void
    {
        $this->expectException(\Throwable::class);

        $registry = new ReversibleCryptoAlgorithmRegistry();

        $registry->get(ReversibleCryptoAlgorithmEnum::AES_256_GCM);
    }
}
