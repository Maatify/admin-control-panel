<?php

declare(strict_types=1);

namespace App\Kernel;

use Slim\App;

class KernelOptions
{
    /**
     * @var string|null
     */
    public ?string $rootPath = null;

    /**
     * @var bool
     */
    public bool $loadEnv = true;

    /**
     * @var bool
     * Register infrastructure middleware (RequestId, Context, Telemetry)
     */
    public bool $registerInfrastructureMiddleware = true;

    /**
     * @var bool
     * Fail fast if AdminRoutes mounted without infrastructure middleware
     */
    public bool $strictInfrastructure = true;

    /**
     * @var (callable(mixed): void)|null
     */
    public $builderHook = null;

    /**
     * @var (callable(App<\Psr\Container\ContainerInterface>): void)|null
     */
    public $bootstrap = null;

    /**
     * @var (callable(App<\Psr\Container\ContainerInterface>): void)|null
     */
    public $routes = null;
}
