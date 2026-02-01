<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO\Store;

class BlockStateDTO
{
    public function __construct(
        public readonly int $level,
        public readonly int $expiresAt
    ) {}
}
