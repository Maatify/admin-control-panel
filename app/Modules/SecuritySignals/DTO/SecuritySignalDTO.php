<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\DTO;

use Maatify\SecuritySignals\Enum\SecuritySeverityEnum;
use Maatify\SecuritySignals\Enum\SecuritySignalTypeEnum;

readonly class SecuritySignalDTO
{
    /**
     * @param string $event_id
     * @param SecuritySignalTypeEnum $signal_type
     * @param SecuritySeverityEnum $severity
     * @param SecuritySignalContextDTO $context
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $event_id,
        public SecuritySignalTypeEnum $signal_type,
        public SecuritySeverityEnum $severity,
        public SecuritySignalContextDTO $context,
        public array $metadata = []
    ) {
    }
}
