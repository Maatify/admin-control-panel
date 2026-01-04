<?php

declare(strict_types=1);

namespace App\Domain\DTO\Audit;

use JsonSerializable;

class ActorAuditLogViewDTO implements JsonSerializable
{
    /**
     * @param array<string, mixed> $changes
     */
    public function __construct(
        public int $auditId,
        public string $targetType,
        public string $targetId,
        public string $action,
        public array $changes,
        public string $createdAt
    ) {
    }

    /**
     * @return array{
     *     audit_id: int,
     *     target_type: string,
     *     target_id: string,
     *     action: string,
     *     changes: array<string, mixed>,
     *     created_at: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'audit_id' => $this->auditId,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'action' => $this->action,
            'changes' => $this->changes,
            'created_at' => $this->createdAt,
        ];
    }
}
