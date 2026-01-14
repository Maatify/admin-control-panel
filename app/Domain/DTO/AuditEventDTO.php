<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use DateTimeImmutable;
use JsonSerializable;

class AuditEventDTO implements JsonSerializable
{
    /**
     * @param int|string|null $target_id
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly ?int $actor_id,
        public readonly string $action,
        public readonly string $target_type,
        public readonly int|string|null $target_id,
        public readonly string $risk_level,
        public readonly array $payload,
        public readonly string $correlation_id,
        public readonly string $request_id,
        public readonly DateTimeImmutable $created_at
    ) {
    }

    /**
     * @return array{actor_id: ?int, action: string, target_type: string, target_id: int|string|null, risk_level: string, payload: array<string, mixed>, correlation_id: string, request_id: string, created_at: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'actor_id' => $this->actor_id,
            'action' => $this->action,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'risk_level' => $this->risk_level,
            'payload' => $this->payload,
            'correlation_id' => $this->correlation_id,
            'request_id' => $this->request_id,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
