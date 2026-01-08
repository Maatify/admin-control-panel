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

use PHPUnit\Framework\TestCase;
use App\Modules\Crypto\Password\PasswordHasher;
use App\Modules\Crypto\Password\DTO\ArgonPolicyDTO;
use App\Modules\Crypto\Password\Exception\PepperUnavailableException;

final class PasswordPepperFailureTest extends TestCase
{
    public function testHashFailsWhenPepperUnavailable(): void
    {
        $hasher = new PasswordHasher(
            new FakePepperProvider(''),
            new ArgonPolicyDTO(1 << 16, 2, 1)
        );

        $this->expectException(PepperUnavailableException::class);

        $hasher->hash('secret');
    }

    public function testVerifyFailsWhenPepperUnavailable(): void
    {
        $hasher = new PasswordHasher(
            new FakePepperProvider(''),
            new ArgonPolicyDTO(1 << 16, 2, 1)
        );

        $this->expectException(PepperUnavailableException::class);

        $hasher->verify('secret', 'dummy');
    }
}
