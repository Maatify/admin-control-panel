<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Crypto;

use Maatify\AdminKernel\Infrastructure\Crypto\RedirectTokenCryptoSignatureProvider;
use Maatify\Crypto\HKDF\HKDFService;
use Maatify\Crypto\KeyRotation\KeyRotationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RedirectTokenCryptoSignatureProviderTest extends TestCase
{
    private KeyRotationService&MockObject $keyRotation;
    private HKDFService&MockObject $hkdf;
    private RedirectTokenCryptoSignatureProvider $provider;

    protected function setUp(): void
    {
        $this->keyRotation = $this->createMock(KeyRotationService::class);
        $this->hkdf = $this->createMock(HKDFService::class);

        $this->keyRotation->method('exportForCrypto')->willReturn([
            'active_key_id' => 'k1',
            'keys' => ['k1' => 'root-key-1'],
        ]);

        $this->hkdf->method('deriveKey')->willReturn('derived-key-material');

        $this->provider = new RedirectTokenCryptoSignatureProvider($this->keyRotation, $this->hkdf);
    }

    public function testIssueGeneratesTokenThatVerifyAndParseAccepts(): void
    {
        $token = $this->provider->issue('/dashboard');
        $parsed = $this->provider->verifyAndParse($token);

        self::assertNotNull($parsed);
        self::assertSame('/dashboard', $parsed->path);
    }

    public function testRejectsExpiredToken(): void
    {
        $token = $this->buildToken('/dashboard', time() - 1);
        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsInvalidSignature(): void
    {
        $token = $this->buildToken('/dashboard', time() + 300, 'bad-signature');
        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsMalformedTokenNoDot(): void
    {
        self::assertNull($this->provider->verifyAndParse('abc'));
    }

    public function testRejectsMalformedTokenTooManyParts(): void
    {
        self::assertNull($this->provider->verifyAndParse('a.b.c'));
    }

    public function testRejectsInvalidBase64Payload(): void
    {
        $sig = $this->sign('***');
        $token = '***.' . $this->b64UrlEncode($sig);

        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsInvalidJsonPayload(): void
    {
        $payload = $this->b64UrlEncode('{invalid-json');
        $token = $payload . '.' . $this->b64UrlEncode($this->sign($payload));

        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsMissingPathField(): void
    {
        $payload = $this->b64UrlEncode((string) json_encode(['exp' => time() + 300], JSON_THROW_ON_ERROR));
        $token = $payload . '.' . $this->b64UrlEncode($this->sign($payload));

        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsMissingExpField(): void
    {
        $payload = $this->b64UrlEncode((string) json_encode(['p' => '/dashboard'], JSON_THROW_ON_ERROR));
        $token = $payload . '.' . $this->b64UrlEncode($this->sign($payload));

        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsExternalUrlPath(): void
    {
        $token = $this->buildToken('https://evil.com', time() + 300);
        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsProtocolRelativePath(): void
    {
        $token = $this->buildToken('//evil.com', time() + 300);
        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsPathContainingSchemeDelimiter(): void
    {
        $token = $this->buildToken('/safe/https://evil', time() + 300);
        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsLoginPath(): void
    {
        $token = $this->buildToken('/login', time() + 300);
        self::assertNull($this->provider->verifyAndParse($token));
    }

    private function buildToken(string $path, int $exp, ?string $signature = null): string
    {
        $payload = $this->b64UrlEncode((string) json_encode(['p' => $path, 'exp' => $exp], JSON_THROW_ON_ERROR));
        $rawSignature = $signature ?? $this->sign($payload);

        return $payload . '.' . $this->b64UrlEncode($rawSignature);
    }

    private function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, 'derived-key-material', true);
    }

    private function b64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
