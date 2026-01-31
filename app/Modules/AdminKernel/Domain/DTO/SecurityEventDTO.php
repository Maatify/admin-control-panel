<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

use DateTimeImmutable;

readonly class SecurityEventDTO
{
    /** @var array<string, mixed> */
    public array $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public ?int $adminId,
        public string $eventName,
        public string $severity,
        array $context,
        public ?string $ipAddress,
        public ?string $userAgent,
        public DateTimeImmutable $occurredAt,
        string $requestId
    ) {
        $this->context = array_merge($context, ['request_id' => $requestId]);
    }
}
