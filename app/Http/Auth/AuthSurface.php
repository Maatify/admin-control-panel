<?php

declare(strict_types=1);

namespace App\Http\Auth;

use Psr\Http\Message\ServerRequestInterface;

final class AuthSurface
{
    /**
     * STRICT RULE (Phase 13.7 LOCK):
     * API = Path starts with /api
     * Web = Path does not start with /api
     */
    public static function isApi(ServerRequestInterface $request): bool
    {
        return str_starts_with($request->getUri()->getPath(), '/api');
    }
}
