<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Security\RedirectToken;

use DateTimeImmutable;
use Maatify\AdminKernel\Domain\Security\RedirectToken\RedirectTokenService;
use Maatify\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use Maatify\Crypto\KeyRotation\KeyRotationService;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;
use Maatify\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;
use Maatify\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RedirectTokenServiceTest extends TestCase
{
    private KeyRotationService $keyRotation;
    private ClockInterface&MockObject $clock;
    private RedirectTokenService $service;

    protected function setUp(): void
    {
        $key = new CryptoKeyDTO(
            id: 'k1',
            material: '12345678901234567890123456789012', // 32 bytes
            status: KeyStatusEnum::ACTIVE,
            createdAt: new DateTimeImmutable()
        );

        $provider = new InMemoryKeyProvider([$key]);
        $policy = new StrictSingleActiveKeyPolicy();
        $this->keyRotation = new KeyRotationService($provider, $policy);

        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new DateTimeImmutable('2024-01-01 12:00:00'));

        $this->service = new RedirectTokenService($this->keyRotation, $this->clock);
    }

    public function testCreateReturnsValidToken(): void
    {
        $path = '/dashboard';
        $token = $this->service->create($path);

        $this->assertNotEmpty($token);
        $this->assertStringContainsString('.', $token);
    }

    public function testVerifyReturnsPathForValidToken(): void
    {
        $path = '/dashboard';
        $token = $this->service->create($path);

        $verifiedPath = $this->service->verify($token);
        $this->assertSame($path, $verifiedPath);
    }

    public function testVerifyRejectsExpiredToken(): void
    {
        $path = '/dashboard';
        $token = $this->service->create($path);

        // Move clock forward past TTL (300s)
        // Need to recreate service with new clock or update mock return
        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new DateTimeImmutable('2024-01-01 12:06:00'));

        $service = new RedirectTokenService($this->keyRotation, $this->clock);

        $this->assertNull($service->verify($token));
    }

    public function testVerifyRejectsTamperedSignature(): void
    {
        $path = '/dashboard';
        $token = $this->service->create($path);

        [$payload, $sig] = explode('.', $token);
        // Base64URL char replacement that keeps length same but changes content
        $sigChar = $sig[0];
        $newChar = $sigChar === 'A' ? 'B' : 'A';
        $sig[0] = $newChar;

        $tamperedToken = $payload . '.' . $sig;

        $this->assertNull($this->service->verify($tamperedToken));
    }

    public function testVerifyRejectsInvalidPaths(): void
    {
        $invalidPaths = [
            'dashboard', // No leading slash
            '//dashboard',
            'https://example.com',
            '/path' . "\r" . 'injection',
            '/path' . "\n" . 'injection',
            '/path#fragment',
            '/login',
            '/login?foo=bar',
            '/2fa/verify',
        ];

        foreach ($invalidPaths as $path) {
            // Manually craft a token with invalid path because create() puts it in JSON and verify() checks it.
            // Even if create() allows it, verify() should reject it.
            $token = $this->service->create($path);
            $this->assertNull($this->service->verify($token), "Should reject path: $path");
        }
    }
}
