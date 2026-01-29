<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Context\AdminContext;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class AdminContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AdminSessionRepositoryInterface $sessionRepository,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. Check for admin_id
        $adminId = $request->getAttribute('admin_id');

        if (!is_int($adminId)) {
            return $handler->handle($request);
        }

        $displayName = null;
        $avatarUrl = null;

        $sessionHash = $request->getAttribute('session_hash');
        if (is_string($sessionHash) && $sessionHash !== '') {
            $identity = $this->sessionRepository->getSessionIdentityByHash($sessionHash);
            if ($identity !== null) {
                $displayName = $identity->displayName;
                $avatarUrl = $identity->avatarUrl;
            }
        }

        // 2. Create Context (DTO is immutable; allow nulls for graceful fallback)
        $context = new AdminContext(
            adminId: $adminId,
            displayName: $displayName,
            avatarUrl: $avatarUrl
        );

        // 3. Attach to Request
        $request = $request->withAttribute(AdminContext::class, $context);

        // 4. Proceed
        return $handler->handle($request);
    }
}
