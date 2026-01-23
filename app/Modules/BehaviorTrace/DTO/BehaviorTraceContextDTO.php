<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\DTO;

use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeInterface;
use DateTimeImmutable;

readonly class BehaviorTraceContextDTO
{
    public function __construct(
        public BehaviorTraceActorTypeInterface $actorType,
        public ?int $actorId,
        public ?string $correlationId,
        public ?string $requestId,
        public ?string $routeName,
        public ?string $ipAddress,
        public ?string $userAgent,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
