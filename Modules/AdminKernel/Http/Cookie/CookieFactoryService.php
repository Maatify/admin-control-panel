<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-14 12:08
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Cookie;

class CookieFactoryService
{
    private const SAME_SITE = 'Strict';
    private const PATH = '/';

    public function createSessionCookie(string $token, bool $isSecure): string
    {
        return sprintf(
            'auth_token=%s; Path=%s; HttpOnly; SameSite=%s; %s',
            $token,
            self::PATH,
            self::SAME_SITE,
            $isSecure ? 'Secure;' : ''
        );
    }

    public function createRememberMeCookie(string $token, bool $isSecure, int $maxAgeSeconds): string
    {
        return sprintf(
            'remember_me=%s; Path=%s; HttpOnly; SameSite=%s; Max-Age=%d; %s',
            $token,
            self::PATH,
            self::SAME_SITE,
            $maxAgeSeconds,
            $isSecure ? 'Secure;' : ''
        );
    }

    public function clearRememberMeCookie(bool $isSecure): string
    {
        return sprintf(
            'remember_me=; Path=%s; HttpOnly; SameSite=%s; Max-Age=0; %s',
            self::PATH,
            self::SAME_SITE,
            $isSecure ? 'Secure;' : ''
        );
    }

    public function clearSessionCookie(bool $isSecure): string
    {
        return sprintf(
            'auth_token=; Path=%s; HttpOnly; SameSite=%s; Max-Age=0; %s',
            self::PATH,
            self::SAME_SITE,
            $isSecure ? 'Secure;' : ''
        );
    }
}
