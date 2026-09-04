<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-21 14:42
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Admin\Reader;

/**
 * AdminProfileReaderInterface
 *
 * Read-only contract for retrieving admin profile data
 * for the Admin Profile (VIEW-only) page.
 *
 * Scope:
 * - UI consumption only
 * - No mutations
 * - No sub-resource aggregation
 * - No permission decisions
 *
 * Phase: 1 (Profile Hub)
 */
interface AdminProfileReaderInterface
{
    /**
     * Retrieve admin profile data for display.
     *
     * Guarantees:
     * - Email is returned MASKED (never raw)
     * - Status is returned as-is (no UI or domain semantics)
     * - Only primary email is included
     *
     * @param   int  $adminId
     *
     * @return array{
     *   admin: array{
     *     id: int,
     *     display_name: string|null,
     *     status: string,
     *     created_at: string
     *   },
     *   email: array{
     *     masked_address: string,
     *     verification_status: string|null,
     *     verified_at: string|null
     *   }
     * }
     */
    public function getProfile(int $adminId): array;
}
