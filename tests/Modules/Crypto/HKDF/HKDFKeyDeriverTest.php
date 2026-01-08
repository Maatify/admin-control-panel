<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 12:22
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\HKDF;

use App\Modules\Crypto\HKDF\HKDFKeyDeriver;
use PHPUnit\Framework\TestCase;

final class HKDFKeyDeriverTest extends TestCase
{
    public function test_same_input_produces_same_output(): void
    {
        $deriver = new HKDFKeyDeriver();

        $rootKey = random_bytes(32);
        $context = 'notification:email:v1';

        $key1 = $deriver->derive($rootKey, $context, 32);
        $key2 = $deriver->derive($rootKey, $context, 32);

        $this->assertSame($key1, $key2);
    }

    public function test_different_contexts_produce_different_keys(): void
    {
        $deriver = new HKDFKeyDeriver();

        $rootKey = random_bytes(32);

        $key1 = $deriver->derive($rootKey, 'notification:email:v1', 32);
        $key2 = $deriver->derive($rootKey, 'notification:sms:v1', 32);

        $this->assertNotSame($key1, $key2);
    }

    public function test_output_length_is_respected(): void
    {
        $deriver = new HKDFKeyDeriver();

        $key = $deriver->derive(random_bytes(32), 'export:file:v1', 16);

        $this->assertSame(16, strlen($key));
    }
}
