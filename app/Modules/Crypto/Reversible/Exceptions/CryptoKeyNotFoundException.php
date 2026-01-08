<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:01
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Crypto\Reversible\Exceptions;

use RuntimeException;

/**
 * CryptoKeyNotFoundException
 *
 * Thrown when a required encryption/decryption key
 * is missing or not available in the provided key set.
 *
 * This is a FAIL-CLOSED exception.
 */
final class CryptoKeyNotFoundException extends RuntimeException
{
}
