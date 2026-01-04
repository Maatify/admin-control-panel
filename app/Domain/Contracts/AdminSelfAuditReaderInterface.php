<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Audit\AuditLogViewDTO;
use App\Domain\DTO\Audit\GetMyActionsQueryDTO;
use App\Domain\DTO\Audit\GetMySecurityEventsQueryDTO;
use App\Domain\DTO\Audit\SecurityEventViewDTO;

interface AdminSelfAuditReaderInterface
{
    /**
     * @return array<AuditLogViewDTO>
     */
    public function getMyActions(GetMyActionsQueryDTO $query): array;

    /**
     * @return array<SecurityEventViewDTO>
     */
    public function getMySecurityEvents(GetMySecurityEventsQueryDTO $query): array;
}
