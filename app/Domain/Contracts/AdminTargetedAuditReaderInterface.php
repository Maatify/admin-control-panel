<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Audit\GetActionsTargetingMeQueryDTO;
use App\Domain\DTO\Audit\TargetAuditLogViewDTO;

interface AdminTargetedAuditReaderInterface
{
    /**
     * @return array<TargetAuditLogViewDTO>
     */
    public function getActionsTargetingMe(GetActionsTargetingMeQueryDTO $query): array;
}
