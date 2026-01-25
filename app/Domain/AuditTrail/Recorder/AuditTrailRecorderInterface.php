<?php

declare(strict_types=1);

namespace App\Domain\AuditTrail\Recorder;

use App\Domain\AuditTrail\DTO\AuditTrailRecordDTO;

interface AuditTrailRecorderInterface
{
    public function record(AuditTrailRecordDTO $dto): void;
}
