<?php

declare(strict_types=1);

namespace App\Kernel;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\App;

class KernelOptions
{
    /**
     * @param string|null $rootPath
     * @param bool $loadEnv
     * @param (callable(ContainerBuilder): void)|null $builderHook
     * @param (callable(App): void)|null $bootstrap
     *
     * @phpstan-param (callable(ContainerBuilder<\DI\Container>): void)|null $builderHook
     * @phpstan-param (callable(App<ContainerInterface>): void)|null $bootstrap
     */
    public function __construct(
        public readonly ?string $rootPath = null,
        public readonly bool $loadEnv = true,
        public readonly mixed $builderHook = null,
        public readonly mixed $bootstrap = null
    ) {
    }
}
