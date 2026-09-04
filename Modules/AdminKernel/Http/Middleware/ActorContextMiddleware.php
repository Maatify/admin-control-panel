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

namespace Maatify\AdminKernel\Http\Middleware;

use Maatify\AdminKernel\Context\ActorContext;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Actor\Actor;
use Maatify\AdminKernel\Domain\Actor\ActorType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;

final class ActorContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ActorContext $actorContext,
        private readonly ContainerInterface $container,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * AdminContext is ONLY present when admin is authenticated.
         * Absence means: system / guest / unauthenticated flow.
         */
        if ($this->container->has(AdminContext::class)) {
            /** @var AdminContext $adminContext */
            $adminContext = $this->container->get(AdminContext::class);

            $this->actorContext->setActor(
                new Actor(ActorType::ADMIN, $adminContext->adminId)
            );
        }

        return $handler->handle($request);
    }
}
