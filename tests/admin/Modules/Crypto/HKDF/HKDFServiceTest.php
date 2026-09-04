<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 12:23
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\HKDF;

use Maatify\Crypto\HKDF\Exceptions\InvalidRootKeyException;
use Maatify\Crypto\HKDF\HKDFContext;
use Maatify\Crypto\HKDF\HKDFService;
use PHPUnit\Framework\TestCase;

final class HKDFServiceTest extends TestCase
{
    public function test_service_derives_key_successfully(): void
    {
        $service = new HKDFService();

        $rootKey = random_bytes(32);
        $context = new HKDFContext('notification:telegram:v1');

        $key = $service->deriveKey($rootKey, $context, 32);

        $this->assertSame(32, strlen($key));
    }

    public function test_service_rejects_invalid_root_key(): void
    {
        $this->expectException(InvalidRootKeyException::class);

        $service = new HKDFService();

        $service->deriveKey(
            'weak-key',
            new HKDFContext('notification:email:v1'),
            32
        );
    }
}
