<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 09:32
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\SecurityEvents\Enum;

/**
 * Defines severity levels for security events.
 *
 * Severity reflects the potential security impact and
 * urgency of the event, not its authority level.
 *
 * These levels are intended for:
 * - monitoring
 * - alerting
 * - filtering
 * - aggregation
 *
 * They MUST NOT be used to infer authorization decisions.
 */
enum SecurityEventSeverityEnum: string
{
    /**
     * Informational security signal with no immediate risk.
     *
     * Example:
     * - Successful login
     * - Successful step-up verification
     */
    case INFO = 'info';

    /**
     * Suspicious or abnormal behavior requiring attention.
     *
     * Example:
     * - Failed login attempt
     * - Permission denied
     */
    case WARNING = 'warning';

    /**
     * Security failure that MUST be recorded
     * but does not yet represent an incident.
     */
    case ERROR = 'error';

    /**
     * High-risk security incident or repeated abuse pattern.
     *
     * Example:
     * - Brute-force detection
     * - Multiple step-up failures
     */
    case CRITICAL = 'critical';
}
