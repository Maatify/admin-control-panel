<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 13:07
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Telemetry\Enum;

/**
 * Telemetry event types (explicit, version-safe, and analytics-friendly).
 *
 * IMPORTANT:
 * - Keep names stable.
 * - Add new cases instead of changing existing ones.
 */
enum TelemetryEventTypeEnum: string
{
    case HTTP_REQUEST_START = 'http_request_start';
    case HTTP_REQUEST_END = 'http_request_end';

    case RATE_LIMIT_HIT = 'rate_limit_hit';
    case CACHE_MISS = 'cache_miss';
    case CACHE_HIT = 'cache_hit';

    case DB_QUERY_SLOW = 'db_query_slow';
    case EXTERNAL_CALL_SLOW = 'external_call_slow';
    case EXTERNAL_CALL_FAIL = 'external_call_fail';

    case WORKER_JOB_START = 'worker_job_start';
    case WORKER_JOB_END = 'worker_job_end';
    case WORKER_JOB_FAIL = 'worker_job_fail';
}
