<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Service\PasswordService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PasswordServiceTest extends TestCase
{
    private string $pepper = 'test-pepper';
    private string $oldPepper = 'old-pepper';
    private PasswordService $service;

    protected function setUp(): void
    {
        $this->service = new PasswordService($this->pepper, $this->oldPepper);
    }

    public function testHashAndVerifySuccess(): void
    {
        $password = 'secret123';
        $hash = $this->service->hash($password);

        $this->assertTrue($this->service->verify($password, $hash));
        $this->assertFalse($this->service->verify('wrong', $hash));
    }

    public function testVerifyOldPepperSuccess(): void
    {
        // Simulate hash created with old pepper
        $password = 'oldsecret';
        $oldPeppered = hash_hmac('sha256', $password, $this->oldPepper);
        $hash = password_hash($oldPeppered, PASSWORD_ARGON2ID);

        $this->assertTrue($this->service->verify($password, $hash));
    }

    public function testVerifyLegacyHash(): void
    {
        $password = 'legacy123';
        // Simulate legacy bcrypt hash (no pepper)
        $legacyHash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertTrue($this->service->verify($password, $legacyHash));
    }

    public function testVerifyFailsOnInvalidAll(): void
    {
        $password = 'secret123';
        $hash = $this->service->hash($password);

        $this->assertFalse($this->service->verify('wrong', $hash));
    }

    public function testConstructorThrowsOnEmptyPepper(): void
    {
        $this->expectException(RuntimeException::class);
        new PasswordService('');
    }
}
