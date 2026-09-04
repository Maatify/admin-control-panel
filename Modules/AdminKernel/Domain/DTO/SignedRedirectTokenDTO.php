<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

final readonly class SignedRedirectTokenDTO
{
    public function __construct(
        public string $path,
        public int $expiresAt,
    ) {
    }
}
