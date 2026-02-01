<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO\Internal;

class PipelineScoreDTO
{
    public function __construct(
        public readonly int $value,
        public readonly int $updatedAt,
        public readonly bool $isFromV1
    ) {}
}
