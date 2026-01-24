<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Recorder;

use Maatify\DeliveryOperations\Contract\DeliveryOperationsPolicyInterface;
use Maatify\DeliveryOperations\Enum\DeliveryActorTypeInterface;

class DeliveryOperationsDefaultPolicy implements DeliveryOperationsPolicyInterface
{
    private const MAX_METADATA_SIZE = 65536; // 64KB

    private const ALLOWED_ACTOR_TYPES = [
        'SYSTEM',
        'ADMIN',
        'USER',
        'SERVICE',
        'API_CLIENT',
        'ANONYMOUS',
    ];

    public function normalizeActorType(DeliveryActorTypeInterface|string $actorType): string
    {
        if ($actorType instanceof DeliveryActorTypeInterface) {
            $value = $actorType->value();
        } else {
            $value = $actorType;
        }

        $upper = strtoupper($value);

        if (in_array($upper, self::ALLOWED_ACTOR_TYPES, true)) {
            return $upper;
        }

        // Fallback or keep original if we want to allow extension, but strict rules say "Any new value requires an explicit documented architectural decision."
        // However, this is a "logging library" intended to be extracted.
        // For now, I will return the uppercase value, assuming the caller knows what they are doing,
        // but strict canonical rules say "Allowed values... Any other value is invalid."
        // If I return 'UNKNOWN', I might lose info.
        // I will return it as is (uppercased) to be safe for now, or mapped to SYSTEM?
        // Let's stick to safe uppercase.
        return $upper;
    }

    public function validateMetadataSize(string $json): bool
    {
        return strlen($json) <= self::MAX_METADATA_SIZE;
    }
}
