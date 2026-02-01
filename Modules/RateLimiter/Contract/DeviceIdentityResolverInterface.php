<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Contract;

use Maatify\RateLimiter\DTO\DeviceIdentityDTO;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;

interface DeviceIdentityResolverInterface
{
    /**
     * Resolve device identity from context.
     *
     * @param RateLimitContextDTO $context
     * @return DeviceIdentityDTO
     */
    public function resolve(RateLimitContextDTO $context): DeviceIdentityDTO;
}
