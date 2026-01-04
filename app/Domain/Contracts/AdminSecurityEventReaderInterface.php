<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Audit\GetMySecurityEventsQueryDTO;
use App\Domain\DTO\Audit\SecurityEventViewDTO;

interface AdminSecurityEventReaderInterface
{
    /**
     * @return array<SecurityEventViewDTO>
     */
    public function getMySecurityEvents(GetMySecurityEventsQueryDTO $query): array;
}
