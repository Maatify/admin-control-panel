<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Context\AdminContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AdminContextMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $adminId = $request->getAttribute('admin_id');

        if (is_int($adminId)) {
            $context = new AdminContext($adminId);
            $request = $request->withAttribute(AdminContext::class, $context);
        }

        return $handler->handle($request);
    }
}
