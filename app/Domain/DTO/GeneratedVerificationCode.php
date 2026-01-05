<?php

declare(strict_types=1);

namespace App\Domain\DTO;

readonly class GeneratedVerificationCode
{
    public function __construct(
        public VerificationCode $entity,
        public string $plainCode
    ) {
    }
}
