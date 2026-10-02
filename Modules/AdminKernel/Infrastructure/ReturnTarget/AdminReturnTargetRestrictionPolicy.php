<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\ReturnTarget;

use Maatify\ReturnTarget\Validation\ReturnTargetRestrictionPolicyInterface;

/**
 * Admin-specific, restrict-only return-target policy.
 *
 * Rejects the login page itself so a post-login redirect can never loop back
 * to /login. Canonical safety validation stays owned by maatify/php-return-target.
 */
final class AdminReturnTargetRestrictionPolicy implements ReturnTargetRestrictionPolicyInterface
{
    private const LOGIN_PATH = '/login';

    public function allows(string $inspectionTarget): bool
    {
        $path = parse_url($inspectionTarget, PHP_URL_PATH);

        return $path !== self::LOGIN_PATH;
    }
}
