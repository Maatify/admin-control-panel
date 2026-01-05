<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Enum\Scope;
use DateTimeImmutable;
use JsonSerializable;

readonly class StepUpGrant implements JsonSerializable
{
    /**
     * @param array<string, mixed> $contextSnapshot
     */
    public function __construct(
        public int $adminId,
        public string $sessionId,
        public Scope $scope,
        public string $riskContextHash,
        public DateTimeImmutable $issuedAt,
        public DateTimeImmutable $expiresAt,
        public bool $singleUse,
        public array $contextSnapshot = []
    ) {
    }

    /**
     * @return array{admin_id: int, session_id: string, scope: string, risk_context_hash: string, issued_at: string, expires_at: string, single_use: bool, context_snapshot: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            'admin_id' => $this->adminId,
            'session_id' => $this->sessionId,
            'scope' => $this->scope->value,
            'risk_context_hash' => $this->riskContextHash,
            'issued_at' => $this->issuedAt->format(DateTimeImmutable::ATOM),
            'expires_at' => $this->expiresAt->format(DateTimeImmutable::ATOM),
            'single_use' => $this->singleUse,
            'context_snapshot' => $this->contextSnapshot,
        ];
    }
}
