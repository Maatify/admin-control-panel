<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 13:22
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\Password;

use PHPUnit\Framework\TestCase;
use App\Modules\Crypto\Password\PasswordHasher;
use App\Modules\Crypto\Password\DTO\ArgonPolicyDTO;

final class RehashBehaviorTest extends TestCase
{
    public function testNeedsRehashTrueWhenPolicyChanges(): void
    {
        $pepper = new FakePepperProvider('pepper-A');

        $oldHasher = new PasswordHasher(
            $pepper,
            new ArgonPolicyDTO(1 << 15, 1, 1)
        );

        $hash = $oldHasher->hash('secret');

        $newHasher = new PasswordHasher(
            $pepper,
            new ArgonPolicyDTO(1 << 16, 2, 1)
        );

        $this->assertTrue(
            $newHasher->needsRehash($hash)
        );
    }

    public function testNeedsRehashFalseForSamePolicy(): void
    {
        $hasher = new PasswordHasher(
            new FakePepperProvider('pepper-A'),
            new ArgonPolicyDTO(1 << 16, 2, 1)
        );

        $hash = $hasher->hash('secret');

        $this->assertFalse(
            $hasher->needsRehash($hash)
        );
    }
}
