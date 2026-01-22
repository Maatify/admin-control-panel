<?php

declare(strict_types=1);

namespace App\Modules\TelemetryLogging\DTO;

use App\Modules\TelemetryLogging\Enum\TelemetryLevelEnum;

readonly class TelemetryEventDTO
{
    /**
     * @param string $eventId UUID
     * @param string $eventKey
     * @param TelemetryLevelEnum $severity
     * @param TelemetryContextDTO $context
     * @param int|null $durationMs
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $eventId,
        public string $eventKey,
        public TelemetryLevelEnum $severity,
        public TelemetryContextDTO $context,
        public ?int $durationMs,
        public ?array $metadata
    ) {
    }
}
