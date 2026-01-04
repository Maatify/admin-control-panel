<?php

declare(strict_types=1);

namespace App\Domain\DTO\Audit;

use JsonSerializable;

class TargetAuditLogViewDTO implements JsonSerializable
{
    /**
     * @param array<string, mixed> $changes
     */
    public function __construct(
        public int $auditId,
        public ?int $actorAdminId,
        public string $action,
        public array $changes,
        public string $createdAt
    ) {
    }

    /**
     * @return array{
     *     audit_id: int,
     *     actor_admin_id: int|null,
     *     action: string,
     *     changes: array<string, mixed>,
     *     created_at: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'audit_id' => $this->auditId,
            'actor_admin_id' => $this->actorAdminId,
            'action' => $this->action,
            'changes' => $this->changes,
            'created_at' => $this->createdAt,
        ];
    }
}
