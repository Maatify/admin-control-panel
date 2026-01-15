<?php

declare(strict_types=1);

namespace Tests\Application\SecurityEvents;

use App\Application\SecurityEvents\HttpSecurityEventAdminRecorder;
use App\Context\RequestContext;
use App\Domain\SecurityEvents\Recorder\SecurityEventRecorder;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use App\Modules\SecurityEvents\Infrastructure\Mysql\SecurityEventLoggerMysqlRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;

final class HttpSecurityEventAdminRecorderTest extends TestCase
{
    private HttpSecurityEventAdminRecorder $recorder;
    private RequestContext $context;

    protected function setUp(): void
    {
        MySQLTestHelper::truncate('security_events');

        // Setup real infrastructure
        $logger = new SecurityEventLoggerMysqlRepository(MySQLTestHelper::pdo());
        $domainRecorder = new SecurityEventRecorder($logger);

        // Setup RequestContext
        $this->context = new RequestContext(
            requestId: 'req-http-test',
            ipAddress: '192.168.1.100',
            userAgent: 'IntegrationAgent/1.0',
            routeName: 'admin.dashboard'
        );

        $this->recorder = new HttpSecurityEventAdminRecorder(
            $domainRecorder,
            $this->context
        );
    }

    protected function tearDown(): void
    {
        MySQLTestHelper::truncate('security_events');
    }

    public function test_full_http_to_db_flow_with_actor_and_metadata(): void
    {
        $actorId = 55;
        $eventType = SecurityEventTypeEnum::LOGIN_SUCCEEDED;
        $severity = SecurityEventSeverityEnum::INFO;
        $metadata = ['method' => '2fa', 'provider' => 'google'];

        // Action
        $this->recorder->record($actorId, $eventType, $severity, $metadata);

        // Assert
        $pdo = MySQLTestHelper::pdo();
        $stmt = $pdo->query("SELECT * FROM security_events");
        $rows = $stmt->fetchAll();

        $this->assertCount(1, $rows);
        $row = $rows[0];

        // 1. Full HTTP -> DB flow verification
        $this->assertEquals($this->context->requestId, $row['request_id']);
        $this->assertEquals($this->context->ipAddress, $row['ip_address']);
        $this->assertEquals($this->context->userAgent, $row['user_agent']);
        $this->assertEquals($this->context->routeName, $row['route_name']);

        // 2. Actor handling verification
        $this->assertEquals('admin', $row['actor_type']);
        $this->assertEquals($actorId, $row['actor_id']);

        // 3. Metadata propagation verification
        $this->assertJsonStringEqualsJsonString(
            json_encode($metadata),
            $row['metadata']
        );

        // Other fields
        $this->assertEquals($eventType->value, $row['event_type']);
        $this->assertEquals($severity->value, $row['severity']);
        $this->assertNotNull($row['occurred_at']);
    }
}
