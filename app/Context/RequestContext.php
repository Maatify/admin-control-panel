<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-12 12:30
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Context;

/**
 * Request-scoped context carrying request metadata for logging,
 * auditing, and tracing purposes.
 *
 * NOTE:
 * - This object is request-scoped.
 * - It must NOT be injected into domain services.
 * - It is intended to be created in HTTP middleware
 *   and propagated via request attributes.
 */
final readonly class RequestContext
{
    public function __construct(
        public string $requestId,
        public string $ipAddress,
        public string $userAgent,

        // Extended (optional – backward compatible)
        public ?string $routeName = null,
        public ?string $method = null,
        public ?string $path = null,
    )
    {
    }
}
