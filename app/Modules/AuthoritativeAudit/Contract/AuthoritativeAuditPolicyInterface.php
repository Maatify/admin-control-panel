<?php

declare(strict_types=1);

namespace Maatify\AuthoritativeAudit\Contract;

use Maatify\AuthoritativeAudit\Enum\AuthoritativeAuditActorTypeInterface;

interface AuthoritativeAuditPolicyInterface
{
    public function normalizeActorType(AuthoritativeAuditActorTypeInterface|string $actorType): string;

    /**
     * @param array<mixed> $payload
     */
    public function validatePayload(array $payload): bool;
}
