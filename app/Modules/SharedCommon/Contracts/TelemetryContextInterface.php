<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 19:38
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\SharedCommon\Contracts;

/**
 * Telemetry context contract (request-scoped data source).
 *
 * Notes:
 * - Module contract only (library-friendly).
 * - Concrete implementations may come from HTTP layer (e.g., RequestContext).
 */
interface TelemetryContextInterface
{
    public function getRequestId(): ?string;

    public function getRouteName(): ?string;

    public function getIpAddress(): ?string;

    public function getUserAgent(): ?string;
}
