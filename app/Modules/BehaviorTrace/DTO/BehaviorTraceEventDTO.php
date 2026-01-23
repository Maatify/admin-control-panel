<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\DTO;

readonly class BehaviorTraceEventDTO
{
    /**
     * @param string $eventId UUID
     * @param string $action
     * @param BehaviorTraceContextDTO $context
     * @param array<mixed>|null $metadata
     */
    public function __construct(
        public string $eventId,
        public string $action,
        public BehaviorTraceContextDTO $context,
        public ?array $metadata
    ) {
    }
}
