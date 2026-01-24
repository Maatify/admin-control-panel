<?php

declare(strict_types=1);

namespace Maatify\AuthoritativeAudit\Recorder;

use Maatify\AuthoritativeAudit\Contract\AuthoritativeAuditPolicyInterface;
use Maatify\AuthoritativeAudit\Enum\AuthoritativeAuditActorTypeInterface;

class AuthoritativeAuditDefaultPolicy implements AuthoritativeAuditPolicyInterface
{
    private const ALLOWED_ACTOR_TYPES = [
        'SYSTEM',
        'ADMIN',
        'USER',
        'SERVICE',
        'API_CLIENT',
        'ANONYMOUS',
    ];

    public function normalizeActorType(AuthoritativeAuditActorTypeInterface|string $actorType): string
    {
        if ($actorType instanceof AuthoritativeAuditActorTypeInterface) {
            $value = $actorType->value();
        } else {
            $value = $actorType;
        }

        $upper = strtoupper($value);

        if (in_array($upper, self::ALLOWED_ACTOR_TYPES, true)) {
            return $upper;
        }

        return $upper;
    }

    /**
     * @param array<mixed> $payload
     */
    public function validatePayload(array $payload): bool
    {
        return $this->scanForSecrets($payload);
    }

    /**
     * @param array<mixed> $data
     */
    private function scanForSecrets(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $lowerKey = strtolower($key);
                if (str_contains($lowerKey, 'password') ||
                    str_contains($lowerKey, 'secret') ||
                    str_contains($lowerKey, 'token')) {
                    return false;
                }
            }
            if (is_array($value)) {
                if (!$this->scanForSecrets($value)) {
                    return false;
                }
            }
        }
        return true;
    }
}
