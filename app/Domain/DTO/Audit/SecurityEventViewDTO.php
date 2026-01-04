<?php

declare(strict_types=1);

namespace App\Domain\DTO\Audit;

use JsonSerializable;

class SecurityEventViewDTO implements JsonSerializable
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public int $eventId,
        public int $adminId,
        public string $eventType,
        public array $context,
        public string $createdAt
    ) {
    }

    /**
     * @return array{
     *     event_id: int,
     *     admin_id: int,
     *     event_type: string,
     *     context: array<string, mixed>,
     *     created_at: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'event_id' => $this->eventId,
            'admin_id' => $this->adminId,
            'event_type' => $this->eventType,
            'context' => $this->context,
            'created_at' => $this->createdAt,
        ];
    }
}
