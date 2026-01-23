<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\DTO;

use DateTimeImmutable;

readonly class SecuritySignalContextDTO
{
    public function __construct(
        public string $actor_type,
        public ?int $actor_id,
        public ?string $request_id,
        public ?string $correlation_id,
        public ?string $route_name,
        public ?string $ip_address,
        public ?string $user_agent,
        public DateTimeImmutable $occurred_at
    ) {
    }
}
