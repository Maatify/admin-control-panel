<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-17 00:30
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Audit;

use DateTimeImmutable;

final readonly class AuditEventDTO
{
    public function __construct(
        public string $actorType,
        public ?int $actorId,

        public string $action,

        public string $targetType,
        public ?int $targetId,

        public string $riskLevel, // LOW | MEDIUM | HIGH | CRITICAL

        /** @var array<string, mixed> */
        public array $payload,

        public string $correlationId,

        public DateTimeImmutable $occurredAt
    )
    {
    }
}
