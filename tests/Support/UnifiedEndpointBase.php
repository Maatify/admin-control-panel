<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Bootstrap\Container;
use DI\Container as DIContainer;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\Support\MySQLTestHelper;

use Psr\Container\ContainerInterface;

abstract class UnifiedEndpointBase extends TestCase
{
    /** @var App<ContainerInterface> */
    protected App $app;
    protected ?PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Initialize Test Database (Schema Load)
        // This ensures the in-memory DB is ready and we have the PDO instance.
        $this->pdo = MySQLTestHelper::pdo();

        // 2. Create Container
        $container = Container::create();

        // 3. Override PDO in Container to use our shared Test PDO
        // This ensures the App uses the SAME database connection as our tests/assertions.
        if ($container instanceof DIContainer) {
            $container->set(PDO::class, $this->pdo);
        } else {
             // Fallback if container implementation changes (unlikely)
             // But for now, we assume PHP-DI
        }

        // 4. Create App
        AppFactory::setContainer($container);
        $this->app = AppFactory::create();

        // 5. Register Middleware & Routes
        $routes = require __DIR__ . '/../../routes/web.php';
        $routes($this->app);

        // 6. Reset Database State (Truncate tables to ensure isolation)
        $this->cleanDatabase();
    }

    protected function cleanDatabase(): void
    {
        $tables = [
            'admins',
            'admin_emails',
            'admin_sessions',
            'admin_passwords',
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
            // Add others as needed
        ];

        foreach ($tables as $table) {
            try {
                MySQLTestHelper::truncate($table);
            } catch (\Exception $e) {
                // Ignore if table doesn't exist (though schema load should have created them)
            }
        }
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function createRequest(string $method, string $path, array $body = []): ServerRequestInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $path);

        // Default headers
        $request = $request->withHeader('Accept', 'application/json');

        if (!empty($body)) {
            $request = $request->withParsedBody($body);
            $request = $request->withHeader('Content-Type', 'application/json');
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
            $conditions[] = "$key = ?";
            $params[] = $value;
        }

        $sql = "SELECT COUNT(*) FROM $table WHERE " . implode(' AND ', $conditions);
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            $this->fail("Failed to prepare SQL: $sql");
        }
        $stmt->execute($params);

        $count = (int)$stmt->fetchColumn();

        $this->assertGreaterThan(0, $count, "Failed asserting that table [$table] has row with: " . json_encode($data));
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
            $conditions[] = "$key = ?";
            $params[] = $value;
        }

        $sql = "SELECT COUNT(*) FROM $table WHERE " . implode(' AND ', $conditions);
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            $this->fail("Failed to prepare SQL: $sql");
        }
        $stmt->execute($params);

        $count = (int)$stmt->fetchColumn();

        $this->assertSame(0, $count, "Failed asserting that table [$table] DOES NOT have row with: " . json_encode($data));
    }
}
