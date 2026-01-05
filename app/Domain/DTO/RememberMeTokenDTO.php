<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use DateTimeImmutable;

readonly class RememberMeTokenDTO
{
    public function __construct(
        public string $selector,
        public string $hashedValidator,
        public int $adminId,
        public DateTimeImmutable $expiresAt,
        public string $userAgentHash
    ) {
    }
}
