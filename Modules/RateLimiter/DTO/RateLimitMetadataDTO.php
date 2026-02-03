<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

use Maatify\RateLimiter\DTO\RateLimitContextMetadataDTO;

class RateLimitMetadataDTO
{
    public function __construct(
        public readonly ?string $signal = null,
        public readonly ?string $cause = null,
        public readonly ?RateLimitContextMetadataDTO $context = null
    ) {}
}
