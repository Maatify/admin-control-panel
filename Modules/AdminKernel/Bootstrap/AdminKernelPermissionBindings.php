<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionMapperV2Interface;
use Maatify\AdminKernel\Domain\Security\Permission\CompositePermissionMapperV2;
use Maatify\AdminKernel\Domain\Security\Permission\KernelPermissionMapProvider;
use Maatify\AdminKernel\Domain\Security\Permission\PermissionMapProviderCollector;
use Maatify\AdminKernel\Domain\Security\Permission\PermissionMapProviderValidator;
use Maatify\AdminKernel\Domain\Security\Permission\SharedPermissionRequirementConverter;
use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;
use Maatify\SharedCommon\Contracts\Security\ProvidesPermissionMapsInterface;
use Psr\Container\ContainerInterface;

final class AdminKernelPermissionBindings
{
    /**
     * @param ContainerBuilder<Container> $builder
     * @param list<object> $packages
     */
    public static function register(ContainerBuilder $builder, array $packages = []): void
    {
        $builder->addDefinitions([
            KernelPermissionMapProvider::class => static fn (): KernelPermissionMapProvider => new KernelPermissionMapProvider(),

            SharedPermissionRequirementConverter::class => static fn (): SharedPermissionRequirementConverter => new SharedPermissionRequirementConverter(),

            PermissionMapProviderCollector::class => static fn (): PermissionMapProviderCollector => new PermissionMapProviderCollector(),

            PermissionMapProviderValidator::class => static fn (): PermissionMapProviderValidator => new PermissionMapProviderValidator(),

            PermissionMapperV2Interface::class => static function (ContainerInterface $c) use ($packages): CompositePermissionMapperV2 {
                /** @var KernelPermissionMapProvider $kernelProvider */
                $kernelProvider = $c->get(KernelPermissionMapProvider::class);

                /** @var PermissionMapProviderCollector $collector */
                $collector = $c->get(PermissionMapProviderCollector::class);

                /** @var PermissionMapProviderValidator $validator */
                $validator = $c->get(PermissionMapProviderValidator::class);

                /** @var SharedPermissionRequirementConverter $converter */
                $converter = $c->get(SharedPermissionRequirementConverter::class);

                /**
                 * Kernel provider must stay first.
                 *
                 * It contains the AdminKernel baseline permissions.
                 *
                 * @var list<PermissionMapProviderInterface> $providers
                 */
                $providers = [
                    $kernelProvider,
                    ...$collector->collect($packages),
                ];

                $validator->assertNoDuplicateRoutes($providers);

                return new CompositePermissionMapperV2(
                    providers: $providers,
                    converter: $converter,
                );
            },
        ]);
    }

    /**
     * @param list<object> $packages
     * @return list<ProvidesPermissionMapsInterface>
     */
    public static function permissionAwarePackages(array $packages): array
    {
        $permissionAwarePackages = [];

        foreach ($packages as $package) {
            if ($package instanceof ProvidesPermissionMapsInterface) {
                $permissionAwarePackages[] = $package;
            }
        }

        return $permissionAwarePackages;
    }
}
