<?php

declare(strict_types=1);

namespace App\Domain\DTO\Audit;

use DateTimeImmutable;

class GetMySecurityEventsQueryDTO
{
    public function __construct(
        public int $adminId,
        public int $page,
        public int $limit,
        public ?string $eventType = null,
        public ?DateTimeImmutable $startDate = null,
        public ?DateTimeImmutable $endDate = null
    ) {
    }
}
