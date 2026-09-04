<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim
 * @since       2026-02-02
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Crypto;

use Maatify\AbuseProtection\Contracts\AbuseSignatureProviderInterface;
use Maatify\Crypto\Contract\CryptoContextProviderInterface;
use Maatify\Crypto\HKDF\HKDFContext;
use Maatify\Crypto\HKDF\HKDFService;
use Maatify\Crypto\KeyRotation\KeyRotationService;
use RuntimeException;

/**
 * AbuseProtectionCryptoSignatureProvider
 *
 * Host adapter for AbuseProtection signing/verification.
 *
 * - Uses KeyRotationService root keys (rotation-aware)
 * - Uses HKDF for domain separation (context-bound derived keys)
 * - Uses HMAC-SHA256 for signing
 *
 * Signature format (LOCKED):
 *   {key_id}.{base64url(hmac_raw)}
 *
 * AbuseProtection module MUST NOT depend on this class.
 */
final class AbuseProtectionCryptoSignatureProvider implements AbuseSignatureProviderInterface
{
    /**
     * HMAC key length (bytes) after HKDF derivation.
     * 32 bytes = 256-bit key.
     */
    private const KEY_LENGTH = 32;

    public function __construct(
        private readonly KeyRotationService $keyRotation,
        private readonly HKDFService $hkdf,
        private readonly CryptoContextProviderInterface $cryptoContextProvider,
    ) {}

    public function sign(string $payload): string
    {
        $export = $this->keyRotation->exportForCrypto();

        /** @var array<string,string> $keys */
        $keys = $export['keys'];
        /** @var string $activeKeyId */
        $activeKeyId = $export['active_key_id'];

        if (!is_array($keys) || $activeKeyId === '') {
            throw new RuntimeException('Crypto keys export is invalid (missing keys or active_key_id).');
        }

        $rootKey = $keys[$activeKeyId] ?? null;
        if (!is_string($rootKey) || $rootKey === '') {
            throw new RuntimeException('Active crypto key not found in export: ' . $activeKeyId);
        }

        $derivedKey = $this->hkdf->deriveKey(
            $rootKey,
            new HKDFContext($this->cryptoContextProvider->abuseProtection()),
            self::KEY_LENGTH
        );

        $macRaw = hash_hmac('sha256', $payload, $derivedKey, true);

        return $activeKeyId . '.' . self::base64UrlEncode($macRaw);
    }

    public function verify(string $payload, string $signature): bool
    {
        $parts = explode('.', $signature, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$keyId, $macB64Url] = $parts;

        if ($keyId === '' || $macB64Url === '') {
            return false;
        }

        $macProvided = self::base64UrlDecode($macB64Url);
        if ($macProvided === null) {
            return false;
        }

        $export = $this->keyRotation->exportForCrypto();

        /** @var array<string,string> $keys */
        $keys = $export['keys'];
        if (!is_array($keys)) {
            return false;
        }

        $rootKey = $keys[$keyId] ?? null;
        if (!is_string($rootKey) || $rootKey === '') {
            // Unknown/rotated-out key id
            return false;
        }
        $this->cryptoContextProvider->abuseProtection();
        $derivedKey = $this->hkdf->deriveKey(
            $rootKey,
            new HKDFContext($this->cryptoContextProvider->abuseProtection()),
            self::KEY_LENGTH
        );

        $macExpected = hash_hmac('sha256', $payload, $derivedKey, true);

        return hash_equals($macExpected, $macProvided);
    }

    public function currentKeyId(): string
    {
        $export = $this->keyRotation->exportForCrypto();
        $activeKeyId = $export['active_key_id'];

        if (!is_string($activeKeyId) || $activeKeyId === '') {
            throw new RuntimeException('Active crypto key id is missing from export.');
        }

        return $activeKeyId;
    }

    private static function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $b64Url): ?string
    {
        $b64 = strtr($b64Url, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad !== 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($b64, true);
        return is_string($decoded) ? $decoded : null;
    }
}
