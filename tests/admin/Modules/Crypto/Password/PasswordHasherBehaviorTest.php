<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 13:19
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\Password;

use Maatify\Crypto\Password\DTO\ArgonPolicyDTO;
use Maatify\Crypto\Password\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class PasswordHasherBehaviorTest extends TestCase
{
    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new PasswordHasher(
            new FakePepperProvider('pepper-A'),
            new ArgonPolicyDTO(1 << 16, 2, 1)
        );
    }

    public function testHashProducesArgonHash(): void
    {
        $hash = $this->hasher->hash('secret');

        $this->assertIsString($hash);
        $this->assertStringStartsWith('$argon2id$', $hash);
    }

    public function testVerifySuccess(): void
    {
        $hash = $this->hasher->hash('secret');

        $this->assertTrue(
            $this->hasher->verify('secret', $hash)
        );
    }

    public function testVerifyFailure(): void
    {
        $hash = $this->hasher->hash('secret');

        $this->assertFalse(
            $this->hasher->verify('wrong', $hash)
        );
    }

    public function testSamePasswordGeneratesDifferentHashes(): void
    {
        $hash1 = $this->hasher->hash('secret');
        $hash2 = $this->hasher->hash('secret');

        $this->assertNotSame($hash1, $hash2);
    }
}
