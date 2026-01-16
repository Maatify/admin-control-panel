<?php

declare(strict_types=1);

namespace Tests\Domain\Telemetry\Reader;

use App\Domain\DTO\Common\PaginationDTO;
use App\Domain\List\ListQueryDTO;
use App\Domain\Telemetry\Reader\PdoTelemetryListReader;
use App\Infrastructure\Query\ResolvedListFilters;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;

class PdoTelemetryListReaderTest extends TestCase
{
    private PdoTelemetryListReader $reader;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure env vars are set for MySQLTestHelper if not already present
        if (!getenv('DB_HOST')) {
             putenv('DB_HOST=127.0.0.1');
             putenv('DB_NAME=admin_control_panel');
             putenv('DB_USER=testuser');
             putenv('DB_PASS=testpass');
        }

        $pdo = MySQLTestHelper::pdo();
        $this->reader = new PdoTelemetryListReader($pdo);

        MySQLTestHelper::truncate('telemetry_traces');
    }

    private function seedTelemetry(array $rows): void
    {
        $pdo = MySQLTestHelper::pdo();

        $sql = 'INSERT INTO telemetry_traces (
            event_key, severity, route_name, request_id,
            actor_type, actor_id, ip_address, user_agent,
            metadata, occurred_at
        ) VALUES (
            :event_key, :severity, :route_name, :request_id,
            :actor_type, :actor_id, :ip_address, :user_agent,
            :metadata, :occurred_at
        )';

        $stmt = $pdo->prepare($sql);

        foreach ($rows as $row) {
            $stmt->execute([
                ':event_key'  => $row['event_key'] ?? 'test.event',
                ':severity'   => $row['severity'] ?? 'info',
                ':route_name' => $row['route_name'] ?? null,
                ':request_id' => $row['request_id'] ?? null,
                ':actor_type' => $row['actor_type'] ?? 'system',
                ':actor_id'   => $row['actor_id'] ?? null,
                ':ip_address' => $row['ip_address'] ?? null,
                ':user_agent' => $row['user_agent'] ?? null,
                ':metadata'   => isset($row['metadata']) ? json_encode($row['metadata']) : null,
                ':occurred_at'=> $row['occurred_at'] ?? date('Y-m-d H:i:s')
            ]);
        }
    }

    public function testBasicListing(): void
    {
        $this->seedTelemetry([
            ['event_key' => 'event.1', 'occurred_at' => '2026-01-01 10:00:00'],
            ['event_key' => 'event.2', 'occurred_at' => '2026-01-01 12:00:00'],
            ['event_key' => 'event.3', 'occurred_at' => '2026-01-01 11:00:00'],
        ]);

        $query = new ListQueryDTO(1, 20, null, [], null, null);
        $filters = new ResolvedListFilters(null, [], null, null);

        $result = $this->reader->getTelemetry($query, $filters);

        $this->assertCount(3, $result->data);
        $this->assertSame('event.2', $result->data[0]->event_key); // Newest first
        $this->assertSame('event.3', $result->data[1]->event_key);
        $this->assertSame('event.1', $result->data[2]->event_key);

        $this->assertSame(3, $result->pagination->total);
        $this->assertSame(3, $result->pagination->filtered);
    }

    public function testGlobalSearch(): void
    {
        $this->seedTelemetry([
            ['event_key' => 'login.success', 'route_name' => 'api.auth', 'request_id' => 'req-123'],
            ['event_key' => 'login.failed',  'route_name' => 'api.login', 'request_id' => 'req-456'],
            ['event_key' => 'logout',        'route_name' => 'api.logout', 'request_id' => 'req-789'],
        ]);

        // Search by event_key
        $query = new ListQueryDTO(1, 20, 'success', [], null, null);
        $filters = new ResolvedListFilters('success', [], null, null);
        $result = $this->reader->getTelemetry($query, $filters);
        $this->assertCount(1, $result->data);
        $this->assertSame('login.success', $result->data[0]->event_key);

        // Search by route_name
        $query = new ListQueryDTO(1, 20, 'api.login', [], null, null);
        $filters = new ResolvedListFilters('api.login', [], null, null);
        $result = $this->reader->getTelemetry($query, $filters);
        $this->assertCount(1, $result->data);
        $this->assertSame('login.failed', $result->data[0]->event_key);

        // Search by request_id
        $query = new ListQueryDTO(1, 20, 'req-789', [], null, null);
        $filters = new ResolvedListFilters('req-789', [], null, null);
        $result = $this->reader->getTelemetry($query, $filters);
        $this->assertCount(1, $result->data);
        $this->assertSame('logout', $result->data[0]->event_key);

        // Search OR logic (matches multiple columns if string matches)
        $this->seedTelemetry([
            ['event_key' => 'common', 'route_name' => 'unique', 'request_id' => 'r1'],
            ['event_key' => 'unique', 'route_name' => 'common', 'request_id' => 'r2'],
        ]);

        $query = new ListQueryDTO(1, 20, 'unique', [], null, null);
        $filters = new ResolvedListFilters('unique', [], null, null);
        $result = $this->reader->getTelemetry($query, $filters);

        // Should find both because 'unique' is in event_key of one and route_name of another
        $this->assertCount(2, $result->data);
    }

    public function testColumnFilters(): void
    {
        $this->seedTelemetry([
            ['event_key' => 'e1', 'route_name' => 'r1', 'request_id' => 'req1', 'actor_type' => 'admin', 'actor_id' => 1, 'ip_address' => '1.1.1.1'],
            ['event_key' => 'e1', 'route_name' => 'r2', 'request_id' => 'req2', 'actor_type' => 'user',  'actor_id' => 2, 'ip_address' => '2.2.2.2'],
            ['event_key' => 'e2', 'route_name' => 'r1', 'request_id' => 'req3', 'actor_type' => 'admin', 'actor_id' => 1, 'ip_address' => '1.1.1.1'],
        ]);

        // Filter by event_key
        $filters = new ResolvedListFilters(null, ['event_key' => 'e1'], null, null);
        $result = $this->reader->getTelemetry(new ListQueryDTO(1, 20, null, [], null, null), $filters);
        $this->assertCount(2, $result->data);
        foreach ($result->data as $item) $this->assertSame('e1', $item->event_key);

        // Filter by route_name
        $filters = new ResolvedListFilters(null, ['route_name' => 'r1'], null, null);
        $result = $this->reader->getTelemetry(new ListQueryDTO(1, 20, null, [], null, null), $filters);
        $this->assertCount(2, $result->data);

        // Filter by request_id
        $filters = new ResolvedListFilters(null, ['request_id' => 'req2'], null, null);
        $result = $this->reader->getTelemetry(new ListQueryDTO(1, 20, null, [], null, null), $filters);
        $this->assertCount(1, $result->data);
        $this->assertSame('req2', $result->data[0]->request_id);

        // Filter by actor_type
        $filters = new ResolvedListFilters(null, ['actor_type' => 'user'], null, null);
        $result = $this->reader->getTelemetry(new ListQueryDTO(1, 20, null, [], null, null), $filters);
        $this->assertCount(1, $result->data);
        $this->assertSame('user', $result->data[0]->actor_type);

        // Filter by actor_id
        $filters = new ResolvedListFilters(null, ['actor_id' => '1'], null, null);
        $result = $this->reader->getTelemetry(new ListQueryDTO(1, 20, null, [], null, null), $filters);
        $this->assertCount(2, $result->data);

        // Filter by ip_address
        $filters = new ResolvedListFilters(null, ['ip_address' => '2.2.2.2'], null, null);
        $result = $this->reader->getTelemetry(new ListQueryDTO(1, 20, null, [], null, null), $filters);
        $this->assertCount(1, $result->data);
        $this->assertSame('2.2.2.2', $result->data[0]->ip_address);

        // Combined filters (AND logic)
        $filters = new ResolvedListFilters(null, ['event_key' => 'e1', 'actor_type' => 'admin'], null, null);
        $result = $this->reader->getTelemetry(new ListQueryDTO(1, 20, null, [], null, null), $filters);
        $this->assertCount(1, $result->data);
        $this->assertSame('req1', $result->data[0]->request_id);
    }

    public function testDateRangeFiltering(): void
    {
        $this->seedTelemetry([
            ['event_key' => 'old', 'occurred_at' => '2026-01-01 10:00:00'],
            ['event_key' => 'mid', 'occurred_at' => '2026-01-02 10:00:00'],
            ['event_key' => 'new', 'occurred_at' => '2026-01-03 10:00:00'],
        ]);

        // From
        $filters = new ResolvedListFilters(null, [], new DateTimeImmutable('2026-01-02 00:00:00'), null);
        $result = $this->reader->getTelemetry(new ListQueryDTO(1, 20, null, [], null, null), $filters);
        $this->assertCount(2, $result->data);
        $this->assertSame('new', $result->data[0]->event_key);
        $this->assertSame('mid', $result->data[1]->event_key);

        // To
        $filters = new ResolvedListFilters(null, [], null, new DateTimeImmutable('2026-01-02 23:59:59'));
        $result = $this->reader->getTelemetry(new ListQueryDTO(1, 20, null, [], null, null), $filters);
        $this->assertCount(2, $result->data);
        $this->assertSame('mid', $result->data[0]->event_key);
        $this->assertSame('old', $result->data[1]->event_key);

        // Range
        $filters = new ResolvedListFilters(null, [], new DateTimeImmutable('2026-01-02 00:00:00'), new DateTimeImmutable('2026-01-02 23:59:59'));
        $result = $this->reader->getTelemetry(new ListQueryDTO(1, 20, null, [], null, null), $filters);
        $this->assertCount(1, $result->data);
        $this->assertSame('mid', $result->data[0]->event_key);
    }

    public function testPagination(): void
    {
        $rows = [];
        for ($i = 1; $i <= 25; $i++) {
            $rows[] = [
                'event_key' => 'e' . $i,
                'occurred_at' => sprintf('2026-01-%02d 12:00:00', $i)
            ];
        }
        $this->seedTelemetry($rows);

        // Page 1 (Newest first, so e25 down to e16)
        $query = new ListQueryDTO(1, 10, null, [], null, null);
        $filters = new ResolvedListFilters(null, [], null, null);
        $result = $this->reader->getTelemetry($query, $filters);

        $this->assertCount(10, $result->data);
        $this->assertSame(25, $result->pagination->total);
        $this->assertSame(25, $result->pagination->filtered);
        $this->assertSame('e25', $result->data[0]->event_key);
        $this->assertSame('e16', $result->data[9]->event_key);

        // Page 2 (e15 down to e6)
        $query = new ListQueryDTO(2, 10, null, [], null, null);
        $result = $this->reader->getTelemetry($query, $filters);
        $this->assertCount(10, $result->data);
        $this->assertSame('e15', $result->data[0]->event_key);
        $this->assertSame('e6', $result->data[9]->event_key);

        // Page 3 (e5 down to e1)
        $query = new ListQueryDTO(3, 10, null, [], null, null);
        $result = $this->reader->getTelemetry($query, $filters);
        $this->assertCount(5, $result->data);
        $this->assertSame('e5', $result->data[0]->event_key);
        $this->assertSame('e1', $result->data[4]->event_key);
    }

    public function testMetadataPresence(): void
    {
        $this->seedTelemetry([
            ['event_key' => 'with_meta',    'metadata' => ['foo' => 'bar']],
            ['event_key' => 'empty_meta',   'metadata' => []],
            ['event_key' => 'null_meta',    'metadata' => null],
        ]);

        $query = new ListQueryDTO(1, 20, null, [], null, null);
        $filters = new ResolvedListFilters(null, [], null, null);

        $result = $this->reader->getTelemetry($query, $filters);

        $this->assertCount(3, $result->data);

        // Find items by key
        $items = [];
        foreach ($result->data as $item) {
            $items[$item->event_key] = $item;
        }

        $this->assertTrue($items['with_meta']->has_metadata);
        $this->assertFalse($items['empty_meta']->has_metadata);
        $this->assertFalse($items['null_meta']->has_metadata);
    }
}
