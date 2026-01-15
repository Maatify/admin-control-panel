<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 12:20
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\SecurityEvents\Enum;

/**
 * Actor types responsible for a security event.
 *
 * This enum defines WHO triggered the event,
 * not HOW it was triggered.
 */
enum SecurityEventActorTypeEnum: string
{
    case ADMIN = 'admin';
    case SYSTEM = 'system';
    case ANONYMOUS = 'anonymous';
    case INTEGRATION = 'integration';
}
