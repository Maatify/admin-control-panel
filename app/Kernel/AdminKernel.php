<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Bootstrap\Container;
use Slim\App;
use Slim\Factory\AppFactory;

class AdminKernel
{
    /**
     * @param callable(mixed): void|null $builderHook
     * @return App<\Psr\Container\ContainerInterface>
     */
    public static function boot(?callable $builderHook = null): App
    {
        // Create Container (This handles ENV loading and AdminConfigDTO)
        $container = Container::create($builderHook);

        // Create App
        AppFactory::setContainer($container);
        /** @var App<\Psr\Container\ContainerInterface> $app */
        $app = AppFactory::create();

        // Delegate HTTP bootstrap logic
        (require __DIR__ . '/../Bootstrap/http.php')($app);

        return $app;
    }
}
