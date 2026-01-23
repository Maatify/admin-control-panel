<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\DTO;

readonly class BehaviorTraceEventDTO
{
    /**
     * @param string $eventId UUID
     * @param string $action
     * @param string|null $entityType
     * @param int|null $entityId
     * @param BehaviorTraceContextDTO $context
     * @param array<mixed>|null $metadata
     */
    public function __construct(
        public string $eventId,
        public string $action,
        public ?string $entityType,
        public ?int $entityId,
        public BehaviorTraceContextDTO $context,
        public ?array $metadata
    ) {
    }
}
