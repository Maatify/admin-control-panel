<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 13:23
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\Password;

use Maatify\Crypto\Password\DTO\ArgonPolicyDTO;
use Maatify\Crypto\Password\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class PepperRotationSimulationTest extends TestCase
{
    public function testPepperRotationInvalidatesOldHashes(): void
    {
        $policy = new ArgonPolicyDTO(1 << 16, 2, 1);

        $hasherA = new PasswordHasher(
            new FakePepperProvider('pepper-A'),
            $policy
        );

        $hash = $hasherA->hash('secret');

        $hasherB = new PasswordHasher(
            new FakePepperProvider('pepper-B'),
            $policy
        );

        $this->assertFalse(
            $hasherB->verify('secret', $hash)
        );
    }
}
