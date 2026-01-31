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

namespace Maatify\Crypto\Reversible\Exceptions;

use RuntimeException;

/**
 * CryptoDecryptionFailedException
 *
 * Thrown when reversible decryption fails due to:
 * - Invalid authentication tag
 * - Corrupted ciphertext
 * - Incorrect key
 * - Missing or invalid metadata (IV / Tag)
 *
 * This exception MUST always be treated as FAIL-CLOSED.
 */
final class CryptoDecryptionFailedException extends RuntimeException
{
}
