<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Ui;

use App\Bootstrap\Container;
use App\Http\Controllers\Ui\UiDashboardController;
use PDO;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\Support\MySQLTestHelper;

final class UiDashboardControllerTelemetryTest extends TestCase
{
    private PDO $pdo;
    private \Psr\Container\ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = Container::create();
        $this->pdo = $this->container->get(PDO::class);

        MySQLTestHelper::truncate('telemetry_traces');
    }

    public function test_telemetry_is_recorded_on_dashboard_access(): void
    {
        /** @var UiDashboardController $controller */
        $controller = $this->container->get(UiDashboardController::class);

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/dashboard')
            ->withAttribute(\App\Context\RequestContext::class, new \App\Context\RequestContext(
                requestId: 'req-test-123',
                ipAddress: '127.0.0.1',
                userAgent: 'TestAgent'
            ))
            ->withAttribute(\App\Context\AdminContext::class, new \App\Context\AdminContext(999));

        $response = $controller->index($request, new Response());

        $this->assertSame(200, $response->getStatusCode());

        $stmt = $this->pdo->query('SELECT * FROM telemetry_traces WHERE request_id = "req-test-123"');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($row, 'Telemetry trace should exist');
        $this->assertSame('http_request_end', $row['event_key']);
        $this->assertSame('999', (string)$row['actor_admin_id']);
        $this->assertSame('info', $row['severity']);
        $this->assertStringContainsString('UiDashboardController', $row['metadata']);
    }
}
