<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Crypto;

use JsonException;
use Maatify\AdminKernel\Domain\Contracts\Auth\RedirectTokenProviderInterface;
use Maatify\AdminKernel\Domain\DTO\SignedRedirectTokenDTO;
use Maatify\AdminKernel\Domain\Security\Crypto\CryptoContext;
use Maatify\Crypto\HKDF\HKDFContext;
use Maatify\Crypto\HKDF\HKDFService;
use Maatify\Crypto\KeyRotation\KeyRotationService;

final readonly class RedirectTokenCryptoSignatureProvider implements RedirectTokenProviderInterface
{
    private const KEY_LENGTH = 32;
    private const TTL_SECONDS = 300;

    public function __construct(
        private KeyRotationService $keyRotation,
        private HKDFService $hkdf,
    ) {
    }

    public function issue(string $path): string
    {
        $normalizedPath = $this->normalizePathForIssue($path);

        $payload = [
            'p' => $normalizedPath,
            'exp' => time() + self::TTL_SECONDS,
        ];

        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $json = '{"p":"/dashboard","exp":0}';
        }

        $encodedPayload = self::base64UrlEncode($json);

        $signature = $this->signPayload($encodedPayload);

        return $encodedPayload . '.' . self::base64UrlEncode($signature);
    }

    public function verifyAndParse(string $token): ?SignedRedirectTokenDTO
    {
        if (substr_count($token, '.') !== 1) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadPart, $signaturePart] = $parts;
        if ($payloadPart === '' || $signaturePart === '') {
            return null;
        }

        $providedSignature = self::base64UrlDecode($signaturePart);
        if ($providedSignature === null) {
            return null;
        }

        if (!$this->isSignatureValid($payloadPart, $providedSignature)) {
            return null;
        }

        $payloadJson = self::base64UrlDecode($payloadPart);
        if ($payloadJson === null) {
            return null;
        }

        try {
            $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($payload) || !isset($payload['p'], $payload['exp'])) {
            return null;
        }

        if (!is_string($payload['p']) || !is_int($payload['exp'])) {
            return null;
        }

        if ($payload['exp'] < time()) {
            return null;
        }

        if (!$this->isValidInternalPath($payload['p'])) {
            return null;
        }

        return new SignedRedirectTokenDTO($payload['p'], $payload['exp']);
    }

    private function signPayload(string $payload): string
    {
        $export = $this->keyRotation->exportForCrypto();

        /** @var array<string,string> $keys */
        $keys = $export['keys'];
        /** @var string $activeKeyId */
        $activeKeyId = $export['active_key_id'];

        $rootKey = $keys[$activeKeyId] ?? null;
        if (!is_string($rootKey) || $rootKey === '') {
            return '';
        }

        $derivedKey = $this->hkdf->deriveKey(
            $rootKey,
            new HKDFContext(CryptoContext::REDIRECT_TOKEN_V1),
            self::KEY_LENGTH
        );

        return hash_hmac('sha256', $payload, $derivedKey, true);
    }

    private function isSignatureValid(string $payload, string $providedSignature): bool
    {
        $export = $this->keyRotation->exportForCrypto();

        /** @var array<string,string> $keys */
        $keys = $export['keys'];
        if (!is_array($keys) || $keys === []) {
            return false;
        }

        foreach ($keys as $rootKey) {
            if (!is_string($rootKey) || $rootKey === '') {
                continue;
            }

            $derivedKey = $this->hkdf->deriveKey(
                $rootKey,
                new HKDFContext(CryptoContext::REDIRECT_TOKEN_V1),
                self::KEY_LENGTH
            );

            $expectedSignature = hash_hmac('sha256', $payload, $derivedKey, true);
            if (hash_equals($expectedSignature, $providedSignature)) {
                return true;
            }
        }

        return false;
    }

    private function normalizePathForIssue(string $path): string
    {
        $path = trim($path);
        if (!$this->isValidInternalPath($path)) {
            return '/dashboard';
        }

        return $path;
    }

    private function isValidInternalPath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (str_contains($path, "\r") || str_contains($path, "\n")) {
            return false;
        }

        $parts = parse_url($path);
        if ($parts === false) {
            return false;
        }

        if (isset($parts['scheme']) || isset($parts['host'])) {
            return false;
        }

        $parsedPath = $parts['path'] ?? null;
        if (!is_string($parsedPath) || $parsedPath === '') {
            return false;
        }

        if (!str_starts_with($parsedPath, '/')) {
            return false;
        }

        if (str_starts_with($parsedPath, '//')) {
            return false;
        }

        if ($parsedPath === '/login') {
            return false;
        }

        return true;
    }

    private static function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $b64Url): ?string
    {
        if ($b64Url === '' || preg_match('/^[A-Za-z0-9\-_]+$/', $b64Url) !== 1) {
            return null;
        }

        $b64 = strtr($b64Url, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad !== 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($b64, true);
        return is_string($decoded) ? $decoded : null;
    }
}
