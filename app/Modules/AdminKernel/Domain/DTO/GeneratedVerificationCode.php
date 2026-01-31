<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

readonly class GeneratedVerificationCode
{
    public function __construct(
        public VerificationCode $entity,
        public string $plainCode
    ) {
    }
}
