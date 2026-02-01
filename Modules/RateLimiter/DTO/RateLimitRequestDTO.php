<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class RateLimitRequestDTO
{
    public function __construct(
        public readonly string $policyName,
        public readonly int $cost = 1,
        public readonly bool $isPreCheck = false,
        public readonly bool $isFailure = false,
        public readonly bool $isSuccess = false
    ) {}

    public static function checkOnly(string $policyName, int $cost = 1): self
    {
        return new self($policyName, $cost, true, false, false);
    }

    public static function recordFailure(string $policyName, int $cost = 1): self
    {
        return new self($policyName, $cost, false, true, false);
    }

    public static function recordSuccess(string $policyName, int $cost = 1): self
    {
        return new self($policyName, $cost, false, false, true);
    }
}
