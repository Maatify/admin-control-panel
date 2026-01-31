<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 19:37
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\SharedCommon\Contracts;

/**
 * The Admin Control Panel project provides a concrete implementation
 * via Maatify\AdminKernel\Context\RequestContext, which adapts itself to this contract.
 */
interface SecurityEventContextInterface
{
    /**
     * A unique identifier correlating all logs/events
     * generated during the same request lifecycle.
     *
     * @return string|null
     */
    public function getRequestId(): ?string;

    /**
     * Client IP address (IPv4 or IPv6) if available.
     *
     * @return string|null
     */
    public function getIpAddress(): ?string;

    /**
     * Client User-Agent string if available.
     *
     * @return string|null
     */
    public function getUserAgent(): ?string;

    /**
     * Logical route or permission name associated with the request,
     * if known (best-effort).
     *
     * Examples:
     * - admin.login
     * - admin.sessions.revoke
     * - api.admins.create
     *
     * @return string|null
     */
    public function getRouteName(): ?string;
}
