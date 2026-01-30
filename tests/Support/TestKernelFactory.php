<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Kernel\AdminKernel;
use App\Kernel\DTO\AdminRuntimeConfigDTO;
use App\Kernel\KernelOptions;
use Slim\App;

final class TestKernelFactory
{
    /**
     * Creates runtime config DERIVED from environment variables.
     * This ensures the kernel boots with DTOs, but honors the project's env-based workflow.
     */
    public static function createRuntimeConfig(): AdminRuntimeConfigDTO
    {
        return AdminRuntimeConfigDTO::fromArray($_ENV);
    }

    /**
     * @return App<\Psr\Container\ContainerInterface>
     */
    public static function bootApp(KernelOptions $options = null): App
    {
        if ($options === null) {
            $options = new KernelOptions();
            $options->runtimeConfig = self::createRuntimeConfig();
            $options->registerInfrastructureMiddleware = true;
            $options->strictInfrastructure = true;
        }

        if (!isset($options->runtimeConfig)) {
            $options->runtimeConfig = self::createRuntimeConfig();
        }

        return AdminKernel::bootWithOptions($options);
    }
}
