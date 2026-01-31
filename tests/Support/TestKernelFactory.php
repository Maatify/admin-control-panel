<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-31 00:36
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Support;

use Maatify\AdminKernel\Kernel\AdminKernel;
use Maatify\AdminKernel\Kernel\DTO\AdminRuntimeConfigDTO;
use Maatify\AdminKernel\Kernel\KernelOptions;
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
    public static function bootApp(?KernelOptions $options = null): App
    {
        if ($options === null) {
            $options = new KernelOptions();
            $options->runtimeConfig = self::createRuntimeConfig();
            $options->registerInfrastructureMiddleware = true;
            $options->strictInfrastructure = true;
        }

        if (! isset($options->runtimeConfig)) {
            $options->runtimeConfig = self::createRuntimeConfig();
        }

        return AdminKernel::bootWithOptions($options);
    }
}
