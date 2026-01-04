<?php

declare(strict_types=1);

namespace App\Infrastructure\UX;

use App\Domain\DTO\AdminActivityDTO;
use DateTimeImmutable;
use RuntimeException;

final class AdminActivityMapper
{
    /**
     * @param array<string, mixed> $row
     * @return AdminActivityDTO
     */
    public function map(array $row): AdminActivityDTO
    {
        $actorAdminId = $row['actor_admin_id'] ?? null;
        if (!is_int($actorAdminId)) {
            throw new RuntimeException('Invalid actor_admin_id');
        }

        $action = $row['action'] ?? null;
        if (!is_string($action)) {
            throw new RuntimeException('Invalid action');
        }

        $targetType = $row['target_type'] ?? null;
        if (!is_string($targetType)) {
            throw new RuntimeException('Invalid target_type');
        }

        $targetId = $row['target_id'] ?? null;
        if ($targetId !== null && !is_int($targetId)) {
            throw new RuntimeException('Invalid target_id');
        }

        $changesJson = $row['changes'] ?? null;
        if (!is_string($changesJson)) {
            $context = [];
        } else {
            $decoded = json_decode($changesJson, true);
            $context = is_array($decoded) ? $decoded : [];
        }

        $occurredAtString = $row['occurred_at'] ?? null;
        if (!is_string($occurredAtString)) {
            throw new RuntimeException('Invalid occurred_at');
        }
        $occurredAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $occurredAtString);
        if ($occurredAt === false) {
             throw new RuntimeException('Invalid date format for occurred_at');
        }

        return new AdminActivityDTO(
            $actorAdminId,
            $action,
            $targetType,
            $targetId,
            $context,
            $occurredAt
        );
    }
}
