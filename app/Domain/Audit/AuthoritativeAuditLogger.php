<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-17 00:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Domain\DTO\AuditEventDTO;

/**
 * Authoritative Audit Logger (Write-Side)
 *
 * IMPORTANT:
 * ----------
 * This implementation is intentionally INERT (no-op).
 *
 * Rationale:
 * - Commit 2 establishes the authoritative audit contract.
 * - Actual persistence to `audit_outbox` will be enabled
 *   in a dedicated follow-up commit after all hybrid and
 *   telemetry-based audit paths are fully removed and reviewed.
 *
 * DO NOT:
 * - Add telemetry
 * - Swallow errors
 * - Add fallback logic
 *
 * Once activated, this logger MUST be fail-closed.
 */
final class AuthoritativeAuditLogger implements AuthoritativeAuditLoggerInterface
{
    /**
     * IMPORTANT — FROZEN IMPLEMENTATION
     *
     * This logger is intentionally INERT.
     *
     * Activation of write-side persistence to `audit_outbox`
     * MUST ONLY occur in a dedicated commit that:
     *  - Replaces all TODO[AUDIT] call sites
     *  - Removes all telemetry / hybrid audit paths
     *  - Is reviewed as a standalone security change
     *
     * Do NOT activate casually.
     */
    public function log(AuditEventDTO $event): void
    {
        // Intentionally no-op.
        // Authoritative audit write will be enabled in a dedicated commit.
    }
}
