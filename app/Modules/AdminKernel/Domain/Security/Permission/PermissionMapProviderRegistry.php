<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Permission;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;

final class PermissionMapProviderRegistry
{
    /**
     * @var list<PermissionMapProviderInterface>
     */
    private array $providers = [];

    public function add(PermissionMapProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * @return list<PermissionMapProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }
}
