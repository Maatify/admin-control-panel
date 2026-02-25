<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\RedirectToken;

use Maatify\Crypto\KeyRotation\KeyRotationService;
use Maatify\SharedCommon\Contracts\ClockInterface;

final readonly class RedirectTokenService implements RedirectTokenServiceInterface
{
    private const TTL = 300; // 5 minutes

    public function __construct(
        private KeyRotationService $keyRotation,
        private ClockInterface $clock
    ) {
    }

    public function create(string $path): string
    {
        $payload = json_encode([
            'p'   => $path,
            'exp' => $this->clock->now()->getTimestamp() + self::TTL,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $encodedPayload = $this->base64UrlEncode($payload);
        $signature = $this->sign($payload);
        $encodedSignature = $this->base64UrlEncode($signature);

        return $encodedPayload . '.' . $encodedSignature;
    }

    public function verify(string $token): ?string
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $encodedSignature] = $parts;

        $payloadJson = $this->base64UrlDecode($encodedPayload);
        $signature = $this->base64UrlDecode($encodedSignature);

        if ($payloadJson === false || $signature === false) {
            return null;
        }

        // 1. Verify Signature FIRST (before trusting payload)
        $expectedSignature = $this->sign($payloadJson);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        try {
            /** @var array{p?: string, exp?: int} $payload */
            $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!isset($payload['p'], $payload['exp'])) {
            return null;
        }

        $path = $payload['p'];
        $exp = $payload['exp'];

        if (!is_string($path) || !is_int($exp)) {
            return null;
        }

        // 2. Check Expiration
        if ($exp < $this->clock->now()->getTimestamp()) {
            return null;
        }

        // 3. Validate Path
        if (!$this->isPathValid($path)) {
            return null;
        }

        return $path;
    }

    private function sign(string $data): string
    {
        // Use the active key material for HMAC
        // If key rotation happens, verification fails (desired for 5m TTL)
        $key = $this->keyRotation->activeEncryptionKey()->material();
        return hash_hmac('sha256', $data, $key, true);
    }

    private function isPathValid(string $path): bool
    {
        // 1. Must start with '/'
        if (!str_starts_with($path, '/')) {
            return false;
        }

        // 2. Must NOT start with '//' (Protocol-relative URL)
        if (str_starts_with($path, '//')) {
            return false;
        }

        // 3. Must NOT contain '://' (External URL)
        if (str_contains($path, '://')) {
            return false;
        }

        // 4. Must NOT contain CR or LF (Header Injection)
        if (str_contains($path, "\r") || str_contains($path, "\n")) {
            return false;
        }

        // 5. Must NOT contain '#' (Fragment Identifier - usually client side, but strict rule)
        if (str_contains($path, '#')) {
            return false;
        }

        // 6. Must NOT equal '/login' (Redirect Loop)
        if ($path === '/login') {
            return false;
        }

        // 7. Must NOT start with '/login?' (Redirect Loop with query)
        if (str_starts_with($path, '/login?')) {
            return false;
        }

        // 8. Must NOT equal '/2fa/verify' (Avoid loop if 2FA session issue)
        if ($path === '/2fa/verify') {
            return false;
        }

        return true;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string|false
    {
        $data = strtr($data, '-_', '+/');
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode($data, true);
    }
}
