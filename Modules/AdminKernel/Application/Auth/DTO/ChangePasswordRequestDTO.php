<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Auth\DTO;

use Maatify\AdminKernel\Context\RequestContext;

final readonly class ChangePasswordRequestDTO
{
    public function __construct(
        public string $email,
        public string $currentPassword,
        public string $newPassword,
        public RequestContext $requestContext,
    ) {
    }
}
