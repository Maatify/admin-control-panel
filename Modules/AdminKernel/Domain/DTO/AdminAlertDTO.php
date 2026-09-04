<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

use DateTimeImmutable;

final class AdminAlertDTO
{
    public function __construct(
        public readonly string $alertCode,
        public readonly string $description,
        public readonly string $severity,
        public readonly DateTimeImmutable $occurredAt
    ) {
    }
}
