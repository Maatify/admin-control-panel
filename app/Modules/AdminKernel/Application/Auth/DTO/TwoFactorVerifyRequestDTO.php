<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-29 10:06
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Auth\DTO;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Enum\Scope;

final readonly class TwoFactorVerifyRequestDTO
{
    public function __construct(
        public int $adminId,
        public string $sessionId,
        public string $code,
        public Scope $requestedScope,
        public RequestContext $requestContext,
    )
    {
    }
}
