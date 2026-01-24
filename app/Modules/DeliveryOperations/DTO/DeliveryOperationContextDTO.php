<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\DTO;

use DateTimeImmutable;

readonly class DeliveryOperationContextDTO
{
    /**
     * @param string $actorType
     * @param int|null $actorId
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $routeName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $actorType,
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
