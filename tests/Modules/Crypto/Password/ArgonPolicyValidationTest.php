<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 13:20
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\Password;

use Maatify\Crypto\Password\DTO\ArgonPolicyDTO;
use Maatify\Crypto\Password\Exception\InvalidArgonPolicyException;
use PHPUnit\Framework\TestCase;

final class ArgonPolicyValidationTest extends TestCase
{
    public function testInvalidMemoryCost(): void
    {
        $this->expectException(InvalidArgonPolicyException::class);
        new ArgonPolicyDTO(0, 2, 1);
    }

    public function testInvalidTimeCost(): void
    {
        $this->expectException(InvalidArgonPolicyException::class);
        new ArgonPolicyDTO(1024, 0, 1);
    }

    public function testInvalidThreads(): void
    {
        $this->expectException(InvalidArgonPolicyException::class);
        new ArgonPolicyDTO(1024, 2, 0);
    }

    public function testValidPolicy(): void
    {
        $policy = new ArgonPolicyDTO(1024, 2, 1);

        $this->assertSame(1024, $policy->memoryCost);
    }
}
