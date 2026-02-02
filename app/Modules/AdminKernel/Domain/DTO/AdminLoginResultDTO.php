<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

use Maatify\AdminKernel\Domain\DTO\Abuse\AbuseCookieIssueDTO;

final readonly class AdminLoginResultDTO
{
    public function __construct(
        public int $adminId,
        public string $token,
        /**
         * Abuse cookie issued during login (device ↔ session binding).
         * Null only in edge cases (e.g. legacy flows or disabled abuse protection).
         */
        public ?AbuseCookieIssueDTO $abuseCookie = null,
    ) {
    }
}