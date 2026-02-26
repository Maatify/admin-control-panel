<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\RedirectToken;

interface RedirectTokenServiceInterface
{
    /**
     * Create a signed redirect token for the given path.
     *
     * @param string $path The redirect path (e.g., "/dashboard")
     * @return string The signed token
     */
    public function create(string $path): string;

    /**
     * Verify a signed redirect token.
     *
     * @param string $token The token string
     * @return string|null The valid path if verified, null otherwise
     */
    public function verify(string $token): ?string;
}
