<?php

declare(strict_types=1);

namespace App\Http\Auth;

use Psr\Http\Message\ServerRequestInterface;

final class AuthSurface
{
    /**
     * STRICT RULE (Phase 13.7 LOCK):
     * API = Authorization header exists
     * Web = Authorization header absent
     */
    public static function isApi(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('Authorization');
    }
}
