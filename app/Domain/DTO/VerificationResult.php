<?php

declare(strict_types=1);

namespace App\Domain\DTO;

readonly class VerificationResult
{
    public function __construct(
        public bool $success,
        public string $reason = ''
    ) {
    }

    public static function success(): self
    {
        return new self(true);
    }

    public static function failure(string $reason): self
    {
        return new self(false, $reason);
    }
}
