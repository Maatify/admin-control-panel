<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Kernel\AdminKernel;
use App\Kernel\DTO\AdminRuntimeConfigDTO;
use App\Kernel\KernelOptions;
use Slim\App;

final class TestKernelFactory
{
    private const TEST_CONFIG = [
        'APP_ENV' => 'testing',
        'APP_DEBUG' => 'true',
        'APP_NAME' => 'AdminPanelTest',
        'ADMIN_URL' => 'http://localhost',
        'DB_HOST' => '127.0.0.1',
        'DB_NAME' => 'test_db',
        'DB_USER' => 'root',
        'DB_PASS' => 'dummy',
        'PASSWORD_PEPPERS' => '{"1": "test-pepper-secret-must-be-32-chars-long"}',
        'PASSWORD_ACTIVE_PEPPER_ID' => '1',
        'PASSWORD_ARGON2_OPTIONS' => '{"memory_cost": 1024, "time_cost": 2, "threads": 2}',
        'EMAIL_BLIND_INDEX_KEY' => 'test-blind-index-key-32-chars-exactly!!',
        'APP_TIMEZONE' => 'Africa/Cairo',
        'MAIL_HOST' => 'smtp.example.com',
        'MAIL_PORT' => '1025',
        'MAIL_USERNAME' => 'test',
        'MAIL_PASSWORD' => 'test',
        'MAIL_FROM_ADDRESS' => 'admin@example.com',
        'MAIL_FROM_NAME' => 'Admin Panel',
        'CRYPTO_KEYS' => '[{"id": "1", "key": "0000000000000000000000000000000000000000000000000000000000000000"}]',
        'CRYPTO_ACTIVE_KEY_ID' => '1',
        'TOTP_ISSUER' => 'AdminPanelTest',
        'TOTP_ENROLLMENT_TTL_SECONDS' => '3600',
        'RECOVERY_MODE' => 'false',
    ];

    public static function createRuntimeConfig(): AdminRuntimeConfigDTO
    {
        return AdminRuntimeConfigDTO::fromArray(self::TEST_CONFIG);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getTestConfig(): array
    {
        return self::TEST_CONFIG;
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
