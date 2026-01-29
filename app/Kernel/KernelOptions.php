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
