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

use Maatify\Crypto\HKDF\Exceptions\InvalidOutputLengthException;
use Maatify\Crypto\HKDF\Exceptions\InvalidRootKeyException;
use Maatify\Crypto\HKDF\HKDFPolicy;
use PHPUnit\Framework\TestCase;

final class HKDFPolicyTest extends TestCase
{
    public function test_valid_root_key_is_accepted(): void
    {
        HKDFPolicy::assertValidRootKey(random_bytes(32));

        $this->assertTrue(true); // no exception
    }

    public function test_short_root_key_is_rejected(): void
    {
        $this->expectException(InvalidRootKeyException::class);

        HKDFPolicy::assertValidRootKey('short-key');
    }

    public function test_valid_output_length_is_accepted(): void
    {
        HKDFPolicy::assertValidOutputLength(32);

        $this->assertTrue(true);
    }

    public function test_zero_output_length_is_rejected(): void
    {
        $this->expectException(InvalidOutputLengthException::class);

        HKDFPolicy::assertValidOutputLength(0);
    }

    public function test_excessive_output_length_is_rejected(): void
    {
        $this->expectException(InvalidOutputLengthException::class);

        HKDFPolicy::assertValidOutputLength(128);
    }
}
