<?php

declare(strict_types=1);

namespace Maatify\AuthoritativeAudit\DTO;

readonly class AuthoritativeAuditOutboxWriteDTO
{
    /**
     * @param string $eventId
     * @param string $eventKey
     * @param string $riskLevel
     * @param AuthoritativeAuditContextDTO $context
     * @param array<mixed> $payload
     */
    public function __construct(
        public string $eventId,
        public string $eventKey,
        public string $riskLevel,
        public AuthoritativeAuditContextDTO $context,
        public array $payload
    ) {
    }
}
