<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Audit\ActorAuditLogViewDTO;
use App\Domain\DTO\Audit\GetMyActionsQueryDTO;

interface AdminSelfAuditReaderInterface
{
    /**
     * @return array<ActorAuditLogViewDTO>
     */
    public function getMyActions(GetMyActionsQueryDTO $query): array;
}
