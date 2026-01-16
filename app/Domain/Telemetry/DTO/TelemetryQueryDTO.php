<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 13:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Telemetry\DTO;

use App\Domain\Telemetry\Enum\TelemetryActorTypeEnum;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class TelemetryQueryDTO
{
    public TelemetryActorTypeEnum $actorType;
    public ?int $actorId;
    public ?string $eventKey;
    public ?string $severity;
    public DateTimeImmutable $dateFrom;
    public DateTimeImmutable $dateTo;
    public int $page;
    public int $perPage;

    public function __construct(
        TelemetryActorTypeEnum $actorType,
        ?int $actorId,
        ?string $eventKey,
        ?string $severity,
        ?DateTimeImmutable $dateFrom,
        ?DateTimeImmutable $dateTo,
        int $page,
        int $perPage
    )
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be >= 1');
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('PerPage must be between 1 and 100');
        }

        $this->actorType = $actorType;
        $this->actorId = $actorId;
        $this->eventKey = $eventKey;
        $this->severity = $severity;

        // 🔒 Hard default: prevent table scans
        $this->dateFrom = $dateFrom ?? new DateTimeImmutable('-24 hours');
        $this->dateTo = $dateTo ?? new DateTimeImmutable();

        if ($this->dateFrom > $this->dateTo) {
            throw new InvalidArgumentException('dateFrom must be <= dateTo');
        }

        $this->page = $page;
        $this->perPage = $perPage;
    }
}
