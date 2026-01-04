<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Audit\AuditLogViewDTO;
use App\Domain\DTO\Audit\GetActionsTargetingMeQueryDTO;

interface AdminTargetedAuditReaderInterface
{
    /**
     * @return array<AuditLogViewDTO>
     */
    public function getActionsTargetingMe(GetActionsTargetingMeQueryDTO $query): array;
}
