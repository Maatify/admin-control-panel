<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Audit;

use DateTimeImmutable;

class GetMyActionsQueryDTO
{
    public function __construct(
        public int $actorAdminId,
        public int $page,
        public int $limit,
        public ?string $action = null,
        public ?string $targetType = null,
        public ?DateTimeImmutable $startDate = null,
        public ?DateTimeImmutable $endDate = null
    ) {
    }
}
