<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Crypto;

use DateTimeImmutable;
use Maatify\AdminKernel\Infrastructure\Crypto\RedirectTokenCryptoSignatureProvider;
use Maatify\Crypto\HKDF\HKDFContext;
use Maatify\Crypto\HKDF\HKDFService;
use Maatify\Crypto\KeyRotation\DTO\CryptoKeyDTO;
use Maatify\Crypto\KeyRotation\KeyRotationService;
use Maatify\Crypto\KeyRotation\KeyStatusEnum;
use Maatify\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;
use Maatify\Crypto\KeyRotation\Providers\InMemoryKeyProvider;
use PHPUnit\Framework\TestCase;

final class RedirectTokenCryptoSignatureProviderTest extends TestCase
{
    private KeyRotationService $keyRotation;
    private HKDFService $hkdf;
    private RedirectTokenCryptoSignatureProvider $provider;
    private string $rootKey;

    protected function setUp(): void
    {
        $this->rootKey = random_bytes(32);
        $provider = new InMemoryKeyProvider([
            new CryptoKeyDTO(
                id       : 'k1',
                material : $this->rootKey,
                status   : KeyStatusEnum::ACTIVE,
                createdAt: new DateTimeImmutable()
            ),
        ]);

        $this->keyRotation = new KeyRotationService(
            provider: $provider,
            policy  : new StrictSingleActiveKeyPolicy()
        );
        $this->hkdf = new HKDFService();

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

    public function testAcceptsInternalTargetWithQueryString(): void
    {
        $token = $this->buildToken('/products?page=3&search=abc', time() + 300);
        $parsed = $this->provider->verifyAndParse($token);
        self::assertNotNull($parsed);
        self::assertSame('/products?page=3&search=abc', $parsed->path);
    }

    public function testAcceptsInternalTargetWhenQueryContainsHttpsUrl(): void
    {
        $token = $this->buildToken('/page?next=https://example.com', time() + 300);
        $parsed = $this->provider->verifyAndParse($token);
        self::assertNotNull($parsed);
        self::assertSame('/page?next=https://example.com', $parsed->path);
    }

    public function testRejectsLoginPath(): void
    {
        $token = $this->buildToken('/login', time() + 300);
        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsLoginPathWithQuery(): void
    {
        $token = $this->buildToken('/login?r=abc', time() + 300);
        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsPathContainingCarriageReturn(): void
    {
        $token = $this->buildToken("/dashboard\rx", time() + 300);
        self::assertNull($this->provider->verifyAndParse($token));
    }

    public function testRejectsPathContainingLineFeed(): void
    {
        $token = $this->buildToken("/dashboard\nx", time() + 300);
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
        $derivedKey = $this->hkdf->deriveKey(
            $this->rootKey,
            new HKDFContext('redirect:token:v1'),
            32
        );

        return hash_hmac('sha256', $payload, $derivedKey, true);
    }

    private function b64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
