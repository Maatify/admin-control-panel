<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-29 11:51
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Context\AdminContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Views\Twig;

final readonly class TwigAdminContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Twig $twig,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adminContext = $request->getAttribute(AdminContext::class);

        if ($adminContext instanceof AdminContext) {
            $this->twig->getEnvironment()->addGlobal('current_admin', [
                'id'           => $adminContext->adminId,
                'display_name' => $adminContext->displayName ?? 'Admin',
                'avatar_url'   => $adminContext->avatarUrl,
            ]);
        }

        return $handler->handle($request);
    }
}
