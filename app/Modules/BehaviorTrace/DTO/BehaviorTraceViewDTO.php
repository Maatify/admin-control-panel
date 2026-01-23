<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\DTO;

readonly class BehaviorTraceViewDTO
{
    /**
     * @param int $id
     * @param string $eventId
     * @param string $action
     * @param string $resource
     * @param string|null $resourceId
     * @param array<mixed>|null $payload
     * @param BehaviorTraceContextDTO $context
     */
    public function __construct(
        public int $id,
        public string $eventId,
        public string $action,
        public string $resource,
        public ?string $resourceId,
        public ?array $payload,
        public BehaviorTraceContextDTO $context
    ) {
    }
}
