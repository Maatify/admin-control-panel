<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-29 09:54
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Auth\DTO;

use Maatify\AdminKernel\Context\RequestContext;

final readonly class VerifyEmailRequestDTO
{
    public function __construct(
        public string $email,
        public string $otp,
        public RequestContext $requestContext,
    )
    {
    }
}
