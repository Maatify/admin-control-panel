<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-18 03:43
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Context;

use Maatify\AdminKernel\Domain\Actor\Actor;

final class ActorContext
{
    private ?Actor $actor = null;

    public function setActor(Actor $actor): void
    {
        $this->actor = $actor;
    }

    public function hasActor(): bool
    {
        return $this->actor !== null;
    }

    public function getActor(): Actor
    {
        if ($this->actor === null) {
            throw new \RuntimeException('ActorContext accessed before actor was set.');
        }

        return $this->actor;
    }
}
