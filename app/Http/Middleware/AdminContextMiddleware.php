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
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. Check for admin_id
        $adminId = $request->getAttribute('admin_id');

        if (is_int($adminId)) {
            // 2. Create Context
            $context = new AdminContext($adminId);

            // 3. Attach to Request
            $request = $request->withAttribute(AdminContext::class, $context);
        }

        // 4. Proceed
        return $handler->handle($request);
    }
}
