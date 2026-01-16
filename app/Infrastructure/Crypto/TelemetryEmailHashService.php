<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 05:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Infrastructure\Crypto;

use App\Application\Telemetry\Contracts\TelemetryEmailHasherInterface;
use App\Application\Telemetry\DTO\TelemetryEmailHashDTO;
use App\Modules\Crypto\HKDF\HKDFContext;
use App\Modules\Crypto\HKDF\HKDFService;
use App\Modules\Crypto\KeyRotation\KeyRotationService;
use Throwable;

/**
 * HKDF-based telemetry email hashing (non-reversible).
 *
 * Derivation:
 * - root key: active key material from KeyRotationService
 * - derived key: HKDF(info="telemetry:email_hash:v1", len=32)
 * - hash: HMAC-SHA256(normalized_email, derived_key) as hex
 *
 * Best-effort:
 * - Returns null on any failure (caller should omit email_hash metadata).
 */
final readonly class TelemetryEmailHashService implements TelemetryEmailHasherInterface
{
    private const HKDF_INFO = 'telemetry:email_hash:v1';
    private const KEY_LEN = 32;

    /**
     * Algo marker for metadata (optional but recommended).
     * Keep stable once released.
     */
    private const ALGO = 'hmac-sha256/hkdf-v1';

    public function __construct(
        private KeyRotationService $keyRotation,
        private HKDFService $hkdf
    ) {
    }

    public function hashEmail(string $email): ?TelemetryEmailHashDTO
    {
        $normalized = $this->normalizeEmail($email);
        if ($normalized === '') {
            return null;
        }

        try {
            $activeKey = $this->keyRotation->activeEncryptionKey();

            // NOTE:
            // We keep a defensive empty-check here because upstream implementations
            // might change, but we avoid impossible checks flagged by PHPStan.
            $keyId = (string) $activeKey->id();
            $rootKeyMaterial = (string) $activeKey->material();

            if ($keyId === '' || $rootKeyMaterial === '') {
                return null;
            }

            $context = new HKDFContext(self::HKDF_INFO);

            // deriveKey is expected (by phpdoc) to return non-empty-string
            $derivedKey = $this->hkdf->deriveKey(
                rootKey: $rootKeyMaterial,
                context: $context,
                length: self::KEY_LEN
            );

            // hash_hmac returns string (non-empty) for valid inputs
            $hash = hash_hmac('sha256', $normalized, $derivedKey);

            return new TelemetryEmailHashDTO(
                hash: $hash,
                keyId: $keyId,
                algo: self::ALGO
            );
        } catch (Throwable) {
            // best-effort: never throw
            return null;
        }
    }

    private function normalizeEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '') {
            return '';
        }

        return strtolower($email);
    }
}
