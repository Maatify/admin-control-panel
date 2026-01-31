<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Kernel;

use Maatify\AdminKernel\Kernel\DTO\AdminRuntimeConfigDTO;
use Slim\App;

final class KernelOptions
{
    /**
     * Runtime configuration (REQUIRED)
     *
     * @var AdminRuntimeConfigDTO|null
     */
    public ?AdminRuntimeConfigDTO $runtimeConfig = null;

    /**
     * Optional path to the kernel templates directory.
     * If NULL, defaults to the internal templates directory.
     *
     * @var string|null
     */
    public ?string $templatesPath = null;

    /**
     * Optional base URL for admin assets.
     * If NULL, falls back to UiConfigDTO configuration.
     *
     * @var string|null
     */
    public ?string $assetsBaseUrl = null;

    /**
     * Register infrastructure middleware
     * (RequestId, RequestContext, Telemetry)
     *
     * @var bool
     */
    public bool $registerInfrastructureMiddleware = true;

    /**
     * Fail fast if routes are mounted without infrastructure middleware
     *
     * @var bool
     */
    public bool $strictInfrastructure = true;

    /**
     * Optional container builder hook
     *
     * @var (callable(mixed): void)|null
     */
    public $builderHook = null;

    /**
     * Optional HTTP bootstrap
     * (body parsing, error middleware, etc.)
     *
     * @var (callable(App<\Psr\Container\ContainerInterface>): void)|null
     */
    public $bootstrap = null;

    /**
     * Routes registrar
     *
     * @var (callable(App<\Psr\Container\ContainerInterface>): void)|null
     */
    public $routes = null;

    /**
     * Optional path to the routes file.
     * If NULL, defaults to the internal routes/web.php.
     *
     * @var string|null
     */
    public ?string $routesFilePath = null;
}
