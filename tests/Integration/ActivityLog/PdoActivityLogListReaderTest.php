<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 22:34
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Integration\ActivityLog;

use App\Domain\DTO\Common\PaginationDTO;
use App\Domain\List\ListQueryDTO;
use App\Infrastructure\Reader\ActivityLog\PdoActivityLogListReader;
use App\Infrastructure\Query\ResolvedListFilters;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;

final class PdoActivityLogListReaderTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = MySQLTestHelper::pdo();
        MySQLTestHelper::truncate('operational_activity');

        $this->seed();
    }

    private function seed(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO operational_activity
                (event_id, action, actor_type, actor_id, entity_type, entity_id, metadata, ip_address, user_agent, request_id, occurred_at)
             VALUES
                (:event_id, :action, :actor_type, :actor_id, :entity_type, :entity_id, :metadata, :ip_address, :user_agent, :request_id, :occurred_at)'
        );

        // ─────────────────────────────
        // Older log (YESTERDAY)
        // ─────────────────────────────
        $stmt->execute([
            'event_id'    => 'uuid-1',
            'action'      => 'admin.user.update',
            'actor_type'  => 'admin',
            'actor_id'    => 1,
            'entity_type' => 'user',
            'entity_id'   => 42,
            'metadata'    => json_encode(['field' => 'email'], JSON_THROW_ON_ERROR),
            'ip_address'  => '127.0.0.1',
            'user_agent'  => 'PHPUnit',
            'request_id'  => 'req-1',
            'occurred_at' => (new DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s'),
        ]);

        // ─────────────────────────────
        // Newer log (TODAY)
        // ─────────────────────────────
        $stmt->execute([
            'event_id'    => 'uuid-2',
            'action'      => 'admin.login',
            'actor_type'  => 'admin',
            'actor_id'    => 2,
            'entity_type' => null,
            'entity_id'   => null,
            'metadata'    => json_encode([], JSON_THROW_ON_ERROR), // operational_activity metadata is NOT NULL
            'ip_address'  => null,
            'user_agent'  => null,
            'request_id'  => 'req-2',
            'occurred_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_it_returns_paginated_activity_logs(): void
    {
        $reader = new PdoActivityLogListReader($this->pdo);

        $query = new ListQueryDTO(
            page: 1,
            perPage: 10,
            globalSearch: null,
            columnFilters: [],
            dateFrom: null,
            dateTo: null
        );

        $filters = new ResolvedListFilters(
            null,
            [],
            null,
            null
        );

        $result = $reader->getActivityLogs($query, $filters);

        $this->assertCount(2, $result->data);
        $this->assertInstanceOf(PaginationDTO::class, $result->pagination);

        $this->assertSame(1, $result->pagination->page);
        $this->assertSame(10, $result->pagination->perPage);
        $this->assertSame(2, $result->pagination->total);
        $this->assertSame(2, $result->pagination->filtered);

        // Ordered DESC by occurred_at → newest first
        $first = $result->data[0];

        $this->assertSame('admin.login', $first->action);
        $this->assertSame('admin', $first->actor_type);
        $this->assertSame(2, $first->actor_id);
    }

    public function test_it_applies_global_search(): void
    {
        $reader = new PdoActivityLogListReader($this->pdo);

        $query = new ListQueryDTO(
            page: 1,
            perPage: 10,
            globalSearch: 'user.update',
            columnFilters: [],
            dateFrom: null,
            dateTo: null
        );

        $filters = new ResolvedListFilters(
            'user.update',
            [],
            null,
            null
        );

        $result = $reader->getActivityLogs($query, $filters);

        $this->assertCount(1, $result->data);
        $this->assertSame('admin.user.update', $result->data[0]->action);
        $this->assertSame(1, $result->pagination->filtered);
    }

    public function test_it_applies_column_filters(): void
    {
        $reader = new PdoActivityLogListReader($this->pdo);

        $query = new ListQueryDTO(
            page: 1,
            perPage: 10,
            globalSearch: null,
            columnFilters: [
                'actor_id' => '1',
            ],
            dateFrom: null,
            dateTo: null
        );

        $filters = new ResolvedListFilters(
            null,
            [
                'actor_id' => '1',
            ],
            null,
            null
        );

        $result = $reader->getActivityLogs($query, $filters);

        $this->assertCount(1, $result->data);
        $this->assertSame(1, $result->data[0]->actor_id);
        $this->assertSame(1, $result->pagination->filtered);
    }

    public function test_it_applies_date_filter(): void
    {
        $reader = new PdoActivityLogListReader($this->pdo);

        $from = new DateTimeImmutable('today');

        $query = new ListQueryDTO(
            page: 1,
            perPage: 10,
            globalSearch: null,
            columnFilters: [],
            dateFrom: $from,
            dateTo: null
        );

        $filters = new ResolvedListFilters(
            null,
            [],
            $from,
            null
        );

        $result = $reader->getActivityLogs($query, $filters);

        // Only today's log should remain
        $this->assertCount(1, $result->data);
        $this->assertSame('admin.login', $result->data[0]->action);
        $this->assertSame(1, $result->pagination->filtered);
    }
}
