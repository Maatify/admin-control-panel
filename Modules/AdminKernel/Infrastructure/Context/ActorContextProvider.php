<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-18 03:44
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Context;

use Maatify\AdminKernel\Context\ActorContext;
use Maatify\AdminKernel\Domain\Actor\Actor;
use Maatify\AdminKernel\Domain\Contracts\ActorProviderInterface;

final class ActorContextProvider implements ActorProviderInterface
{
    public function __construct(
        private readonly ActorContext $actorContext
    )
    {
    }

    public function getActor(): Actor
    {
        return $this->actorContext->getActor();
    }
}
