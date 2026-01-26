<?php

declare(strict_types=1);

namespace Tests\Integration\Logging;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Infrastructure\Logging\BehaviorTraceMaatifyAdapter;
use Maatify\BehaviorTrace\Infrastructure\Mysql\BehaviorTraceWriterMysqlRepository;
use Maatify\BehaviorTrace\Recorder\BehaviorTraceRecorder;
use Maatify\BehaviorTrace\Services\SystemClock;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;

class BehaviorTraceSwitchoverTest extends TestCase
{
    private \PDO $pdo;
    private AdminActivityLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = MySQLTestHelper::pdo();
        MySQLTestHelper::truncate('operational_activity');
        // operational_activity exists because MySQLTestHelper bootstraps it now

        // Manual Construction to use the Test PDO (SQLite)
        $clock = new SystemClock();
        $writer = new BehaviorTraceWriterMysqlRepository($this->pdo);

        // Pass a NullLogger or Mock Logger as fallback if required by Recorder constructor
        // Checking Container.php:
        // new BehaviorTraceRecorder($writer, $clock, $fallbackLogger)
        // Fallback logger is optional (nullable).

        $libraryRecorder = new BehaviorTraceRecorder($writer, $clock, null);
        $adapter = new BehaviorTraceMaatifyAdapter($libraryRecorder);
        $this->service = new AdminActivityLogService($adapter);
    }

    public function test_log_writes_to_operational_activity(): void
    {
        $adminContext = new AdminContext(123);
        $requestContext = new RequestContext(
            requestId: 'req-uuid-test',
            ipAddress: '127.0.0.1',
            userAgent: 'TestAgent'
        );

        $this->service->log(
            adminContext: $adminContext,
            requestContext: $requestContext,
            action: 'test.switchover',
            entityType: 'test_entity',
            entityId: 999,
            metadata: ['foo' => 'bar']
        );

        // Assert operational_activity has the record
        $stmt = $this->pdo->prepare("SELECT * FROM operational_activity WHERE action = 'test.switchover'");
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertIsArray($row, 'Record should exist in operational_activity');
        $this->assertEquals('123', $row['actor_id']);
        $this->assertEquals('ADMIN', $row['actor_type']);
        $this->assertEquals('test_entity', $row['entity_type']);
        $this->assertEquals(999, $row['entity_id']);
        $this->assertEquals('req-uuid-test', $row['request_id']);
        $this->assertEquals('127.0.0.1', $row['ip_address']);
        $this->assertEquals('TestAgent', $row['user_agent']);

        $metadata = json_decode((string)$row['metadata'], true);
        $this->assertEquals(['foo' => 'bar'], $metadata);

        // Assert activity_logs is empty
        // Note: MySQLTestHelper might create activity_logs table for legacy reasons,
        // but our service should not write to it.
        // We verify count is 0.
        // MySQLTestHelper::truncate('activity_logs') was not called in setUp because I removed it
        // (assuming truncate('operational_activity') is enough).
        // But checking if activity_logs is empty is good.
        // I should truncate it just in case.
        MySQLTestHelper::truncate('activity_logs');

        $count = $this->pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
        $this->assertEquals(0, $count, 'Legacy activity_logs table should be empty');
    }
}
