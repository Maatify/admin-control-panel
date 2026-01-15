<?php

declare(strict_types=1);

namespace Tests\Modules\SecurityEvents;

use App\Modules\SecurityEvents\DTO\SecurityEventDTO;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use App\Modules\SecurityEvents\Exceptions\SecurityEventStorageException;
use App\Modules\SecurityEvents\Infrastructure\Mysql\SecurityEventLoggerMysqlRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;

final class SecurityEventLoggerMysqlRepositoryTest extends TestCase
{
    private SecurityEventLoggerMysqlRepository $repository;

    protected function setUp(): void
    {
        MySQLTestHelper::truncate('security_events');
        $this->repository = new SecurityEventLoggerMysqlRepository(MySQLTestHelper::pdo());
    }

    protected function tearDown(): void
    {
        MySQLTestHelper::truncate('security_events');
    }

    public function test_successful_insert(): void
    {
        $occurredAt = new DateTimeImmutable('2024-01-01 12:00:00');
        $dto = new SecurityEventDTO(
            actorType: 'admin',
            actorId: 123,
            eventType: SecurityEventTypeEnum::LOGIN_FAILED,
            severity: SecurityEventSeverityEnum::WARNING,
            requestId: 'req-123',
            ipAddress: '127.0.0.1',
            userAgent: 'Mozilla/5.0',
            routeName: 'auth.login',
            metadata: ['reason' => 'bad_password'],
            occurredAt: $occurredAt
        );

        $this->repository->log($dto);

        $pdo = MySQLTestHelper::pdo();
        $stmt = $pdo->query("SELECT * FROM security_events");
        $rows = $stmt->fetchAll();

        $this->assertCount(1, $rows);
        $row = $rows[0];

        $this->assertEquals('admin', $row['actor_type']);
        $this->assertEquals(123, $row['actor_id']);
        $this->assertEquals(SecurityEventTypeEnum::LOGIN_FAILED->value, $row['event_type']);
        $this->assertEquals(SecurityEventSeverityEnum::WARNING->value, $row['severity']);
        $this->assertEquals('req-123', $row['request_id']);
        $this->assertEquals('auth.login', $row['route_name']);
        $this->assertEquals('127.0.0.1', $row['ip_address']);
        $this->assertEquals('Mozilla/5.0', $row['user_agent']);

        $this->assertJsonStringEqualsJsonString('{"reason":"bad_password"}', $row['metadata']);

        $this->assertEquals($occurredAt->format('Y-m-d H:i:s'), $row['occurred_at']);
    }

    public function test_occurred_at_defaults_when_null(): void
    {
        $dto = new SecurityEventDTO(
            actorType: 'system',
            actorId: null,
            eventType: SecurityEventTypeEnum::SESSION_EXPIRED,
            severity: SecurityEventSeverityEnum::INFO,
            requestId: null,
            ipAddress: null,
            userAgent: null,
            routeName: null,
            metadata: [],
            occurredAt: null
        );

        $this->repository->log($dto);

        $pdo = MySQLTestHelper::pdo();
        $stmt = $pdo->query("SELECT occurred_at FROM security_events");
        $row = $stmt->fetch();

        $this->assertNotNull($row['occurred_at']);
        // Verify it's recent (within last 10 seconds)
        $dbTime = new DateTimeImmutable($row['occurred_at']);
        $now = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $dbTime->getTimestamp();
        $this->assertLessThan(10, abs($diff));
    }

    public function test_storage_failure(): void
    {
        // Force failure by using an actorType that exceeds VARCHAR(32)
        // Assuming strict SQL mode is enabled which will raise an error for truncation
        $longString = str_repeat('a', 33);

        $dto = new SecurityEventDTO(
            actorType: $longString,
            actorId: 123,
            eventType: SecurityEventTypeEnum::LOGIN_FAILED,
            severity: SecurityEventSeverityEnum::WARNING,
            requestId: 'req-123',
            ipAddress: '127.0.0.1',
            userAgent: 'test-agent',
            routeName: 'test',
            metadata: [],
            occurredAt: new DateTimeImmutable()
        );

        $this->expectException(SecurityEventStorageException::class);
        $this->repository->log($dto);
    }

    public function test_enum_persistence(): void
    {
        // This is effectively covered by test_successful_insert
        // checking that we store string values of Enums

        $dto = new SecurityEventDTO(
            actorType: 'admin',
            actorId: 1,
            eventType: SecurityEventTypeEnum::PERMISSION_DENIED,
            severity: SecurityEventSeverityEnum::CRITICAL,
            requestId: 'req-test',
            ipAddress: '127.0.0.1',
            userAgent: 'test',
            routeName: 'test',
            metadata: [],
            occurredAt: new DateTimeImmutable()
        );

        $this->repository->log($dto);

        $pdo = MySQLTestHelper::pdo();
        $stmt = $pdo->query("SELECT event_type, severity FROM security_events");
        $row = $stmt->fetch();

        // Asserting types are strings
        $this->assertIsString($row['event_type']);
        $this->assertIsString($row['severity']);

        // Asserting values match Enum backing values
        $this->assertEquals('permission_denied', $row['event_type']);
        $this->assertEquals('critical', $row['severity']);
    }
}
