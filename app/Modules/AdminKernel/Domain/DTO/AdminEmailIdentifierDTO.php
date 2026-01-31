<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-23 18:05
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

use Maatify\AdminKernel\Domain\Enum\VerificationStatus;

/**
 * Represents a resolved admin email identifier.
 *
 * This DTO is returned by identifier lookup operations (e.g. blind index lookup)
 * and provides a complete, unambiguous snapshot of the email identity.
 *
 * IMPORTANT:
 * - This DTO is immutable.
 * - It MUST be used instead of returning scalar IDs.
 */
final readonly class AdminEmailIdentifierDTO
{
    public function __construct(
        public int $emailId,
        public int $adminId,
        public VerificationStatus $verificationStatus
    )
    {
    }
}
