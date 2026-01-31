<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-19 11:23
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Support;

final class CorrelationId
{
    /**
     * Generate a new cryptographically secure correlation ID.
     *
     * Rules:
     * - Independent from request_id
     * - Stable across a logical transaction
     * - Safe for logging & persistence
     */
    public static function generate(): string
    {
        return bin2hex(random_bytes(16)); // 32 hex chars
    }
}
