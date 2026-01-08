<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 12:17
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Crypto\HKDF;

use RuntimeException;

final class HKDFKeyDeriver
{
    /**
     * RFC 5869 compliant expand-only HKDF (HMAC-SHA256).
     * Assumes high-entropy root key provided by KeyRotation.
     */
    public function derive(
        string $rootKey,
        string $context,
        int $length
    ): string {
        $hashLen = 32; // SHA-256 output length in bytes
        $blocks  = (int) ceil($length / $hashLen);

        $okm = '';
        $previousBlock = '';

        for ($i = 1; $i <= $blocks; $i++) {
            $data = $previousBlock . $context . chr($i);

            // hash_hmac with raw_output=true always returns a non-empty string
            $previousBlock = hash_hmac('sha256', $data, $rootKey, true);

            $okm .= $previousBlock;
        }

        return substr($okm, 0, $length);
    }
}
