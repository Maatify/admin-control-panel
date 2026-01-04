<?php

declare(strict_types=1);

namespace App\Domain\DTO\Audit;

use DateTimeImmutable;

class GetActionsTargetingMeQueryDTO
{
    public function __construct(
        public int $targetAdminId,
        public int $page,
        public int $limit,
        public ?int $actorAdminId = null,
        public ?string $action = null,
        public ?DateTimeImmutable $startDate = null,
        public ?DateTimeImmutable $endDate = null
    ) {
    }
}
