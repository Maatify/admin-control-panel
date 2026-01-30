<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Contracts\ClockInterface;
use App\Kernel\AdminKernel;
use App\Kernel\KernelOptions;
use App\Kernel\DTO\AdminRuntimeConfigDTO;
use DateTimeImmutable;
use DateTimeZone;
use DI\Container as DIContainer;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

abstract class UnifiedEndpointBase extends TestCase
{
    /** @var App<ContainerInterface> */
    protected App $app;

    protected ?PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();

        // 1️⃣ Initialize Test Config & DB Helper
        $testConfig = TestKernelFactory::getTestConfig();
        MySQLTestHelper::init($testConfig);

        // 2️⃣ Initialize Test Database (shared PDO)
        $this->pdo = MySQLTestHelper::pdo();
        $this->assertInstanceOf(PDO::class, $this->pdo);

        // 3️⃣ Build Runtime Config via Factory
        $runtimeConfig = TestKernelFactory::createRuntimeConfig();

        // 4️⃣ Kernel Options
        $options = new KernelOptions();
        $options->runtimeConfig = $runtimeConfig;
        $options->registerInfrastructureMiddleware = true;
        $options->strictInfrastructure = true;

        $options->builderHook = function ($containerBuilder): void {
            $tz = new DateTimeZone('Africa/Cairo');

            $containerBuilder->addDefinitions([
                ClockInterface::class => function () use ($tz) {
                    return new TestClock(
                        new DateTimeImmutable('2024-01-01 10:00:00', $tz),
                        $tz
                    );
                },
            ]);
        };

        // 5️⃣ Boot Kernel
        $this->app = AdminKernel::bootWithOptions($options);

        // 6️⃣ Override PDO in container to use test PDO
        $container = $this->app->getContainer();
        if ($container instanceof DIContainer) {
            $container->set(PDO::class, $this->pdo);
        }

        // 6️⃣ Reset database state
        $this->cleanDatabase();
    }

    protected function cleanDatabase(): void
    {
        $tables = [
            'system_ownership',
            'admin_roles',
            'role_permissions',
            'roles',
            'admins',
            'admin_emails',
            'admin_sessions',
            'admin_passwords',
            'admin_remember_me_tokens',
            'admin_totp_secrets',
            'admin_notification_preferences',
            'admin_notification_channels',
            'admin_notifications',
            'failed_notifications',
            'email_queue',
            'telegram_queue',
            'audit_outbox',
            'audit_logs',
            'activity_logs',
            'security_events',
            'telemetry_traces',
            'delivery_operations',
            'verification_codes',
            'step_up_grants',
            'permissions',
            'admin_direct_permissions',
        ];

        foreach ($tables as $table) {
            try {
                MySQLTestHelper::truncate($table);
            } catch (\Throwable $e) {
                // Table may not exist in some test scopes — ignore safely
            }
        }
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function createRequest(
        string $method,
        string $path,
        array $body = []
    ): ServerRequestInterface {
        $request = (new ServerRequestFactory())
            ->createServerRequest($method, $path)
            ->withHeader('Accept', 'application/json');

        if ($body !== []) {
            $request = $request
                ->withParsedBody($body)
                ->withHeader('Content-Type', 'application/json');
        }

        return $request;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function assertDatabaseHas(string $table, array $data): void
    {
        if ($this->pdo === null) {
            $this->fail('PDO not initialized');
        }

        $conditions = [];
        $params = [];

        foreach ($data as $key => $value) {
            $conditions[] = "{$key} = ?";
            $params[] = $value;
        }

        $sql = "SELECT COUNT(*) FROM {$table} WHERE " . implode(' AND ', $conditions);
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            $this->fail("Failed to prepare SQL: {$sql}");
        }

        $stmt->execute($params);
        $count = (int) $stmt->fetchColumn();

        $this->assertGreaterThan(
            0,
            $count,
            "Failed asserting that table [{$table}] has row with: " . json_encode($data)
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function assertDatabaseMissing(string $table, array $data): void
    {
        if ($this->pdo === null) {
            $this->fail('PDO not initialized');
        }

        $conditions = [];
        $params = [];

        foreach ($data as $key => $value) {
            $conditions[] = "{$key} = ?";
            $params[] = $value;
        }

        $sql = "SELECT COUNT(*) FROM {$table} WHERE " . implode(' AND ', $conditions);
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            $this->fail("Failed to prepare SQL: {$sql}");
        }

        $stmt->execute($params);
        $count = (int) $stmt->fetchColumn();

        $this->assertSame(
            0,
            $count,
            "Failed asserting that table [{$table}] DOES NOT have row with: " . json_encode($data)
        );
    }
}
