<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-18 03:39
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Actor;

use InvalidArgumentException;

final class Actor
{
    private ActorType $type;
    private int|string|null $id;

    public function __construct(ActorType $type, int|string|null $id)
    {
        $this->assertValid($type, $id);

        $this->type = $type;
        $this->id = $id;
    }

    public function type(): ActorType
    {
        return $this->type;
    }

    public function id(): int|string|null
    {
        return $this->id;
    }

    private function assertValid(ActorType $type, int|string|null $id): void
    {
        match ($type) {
            ActorType::ADMIN => $this->assertAdmin($id),
            ActorType::SYSTEM => $this->assertSystem($id),
            ActorType::EXTERNAL => $this->assertExternal($id),
        };
    }

    private function assertAdmin(int|string|null $id): void
    {
        if (! is_int($id)) {
            throw new InvalidArgumentException(
                'Invalid Actor state: ADMIN actor must have an integer ID.'
            );
        }
    }

    private function assertSystem(int|string|null $id): void
    {
        if ($id !== null) {
            throw new InvalidArgumentException(
                'Invalid Actor state: SYSTEM actor must not have an ID.'
            );
        }
    }

    private function assertExternal(int|string|null $id): void
    {
        if (! is_string($id) || $id === '') {
            throw new InvalidArgumentException(
                'Invalid Actor state: EXTERNAL actor must have a non-empty string ID.'
            );
        }
    }
}
