<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Auth;

use Maatify\AdminKernel\Domain\DTO\SignedRedirectTokenDTO;

interface RedirectTokenProviderInterface
{
    public function issue(string $path): string;

    public function verifyAndParse(string $token): ?SignedRedirectTokenDTO;
}
