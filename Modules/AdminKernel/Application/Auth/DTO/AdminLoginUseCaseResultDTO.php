<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-29 08:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Auth\DTO;

use Maatify\AdminKernel\Domain\DTO\Abuse\AbuseCookieIssueDTO;

final readonly class AdminLoginUseCaseResultDTO
{
    public function __construct(
        public string $authToken,
        public int $authTokenMaxAgeSeconds,
        public ?string $rememberMeToken,
        public int $adminId,
        public ?AbuseCookieIssueDTO $abuseCookie
    ) {
    }
}
