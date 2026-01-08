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

use App\Modules\Crypto\HKDF\HKDFContext;
use App\Modules\Crypto\HKDF\Exceptions\InvalidContextException;
use PHPUnit\Framework\TestCase;

final class HKDFContextTest extends TestCase
{
    public function test_valid_context_is_accepted(): void
    {
        $context = new HKDFContext('notification:email:v1');

        $this->assertSame('notification:email:v1', $context->value());
    }

    public function test_empty_context_is_rejected(): void
    {
        $this->expectException(InvalidContextException::class);

        new HKDFContext('');
    }

    public function test_unversioned_context_is_rejected(): void
    {
        $this->expectException(InvalidContextException::class);

        new HKDFContext('notification:email');
    }
}
