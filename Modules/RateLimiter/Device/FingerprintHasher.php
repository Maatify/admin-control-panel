<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Device;

class FingerprintHasher
{
    public function __construct(
        private readonly string $secret,
        private readonly string $algo = 'sha256'
    ) {}

    public function hash(string $input): string
    {
        return hash_hmac($this->algo, $input, $this->secret);
    }
}
