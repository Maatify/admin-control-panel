<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\ValueObject;

use Maatify\ContentDocuments\Domain\Exception\InvalidActorIdentityException;

final class ActorIdentity
{
    private const ACTOR_TYPE_MAX_LENGTH = 64;
    private const ACTOR_TYPE_PATTERN = '/^[a-z0-9\-]+$/';

    private string $actorType;
    private int $actorId;

    public function __construct(
        string $actorType,
        int $actorId,
    ) {
        $actorType = trim($actorType);

        if ($actorType === '') {
            throw new InvalidActorIdentityException('Actor type must not be empty.');
        }

        if (strlen($actorType) > self::ACTOR_TYPE_MAX_LENGTH) {
            throw new InvalidActorIdentityException('Actor type exceeds max length of ' . self::ACTOR_TYPE_MAX_LENGTH . '.');
        }

        if ($actorType !== strtolower($actorType)) {
            throw new InvalidActorIdentityException('Actor type must be lowercase.');
        }

        if (!preg_match(self::ACTOR_TYPE_PATTERN, $actorType)) {
            throw new InvalidActorIdentityException('Actor type must match pattern: ' . self::ACTOR_TYPE_PATTERN);
        }

        if ($actorId < 1) {
            throw new InvalidActorIdentityException('Actor id must be a positive integer.');
        }

        $this->actorType = $actorType;
        $this->actorId   = $actorId;
    }

    public function equals(ActorIdentity $other): bool
    {
        return $this->actorType === $other->actorType
               && $this->actorId === $other->actorId;
    }

    public function __toString(): string
    {
        return $this->actorType . ':' . (string)$this->actorId;
    }

    public function actorType(): string
    {
        return $this->actorType;
    }

    public function actorId(): int
    {
        return $this->actorId;
    }
}
