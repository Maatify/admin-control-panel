<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class RateLimitContextMetadataDTO
{
    public function __construct(
        public readonly ?string $reason = null,
        public readonly ?string $scope = null
    ) {}
}
