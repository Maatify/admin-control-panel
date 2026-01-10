<?php

declare(strict_types=1);

namespace Tests\Canonical\Sessions;

use App\Domain\DTO\AdminConfigDTO;
use App\Domain\List\ListQueryDTO;
use App\Infrastructure\Query\ResolvedListFilters;
use App\Infrastructure\Reader\Session\PdoSessionListReader;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PdoSessionListReaderTest extends TestCase
{
    private PdoSessionListReader $reader;
    private MockObject|PDO $pdo;
    private MockObject|AdminConfigDTO $config;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->config = $this->createMock(AdminConfigDTO::class);
        $this->reader = new PdoSessionListReader($this->pdo, $this->config);
    }

    private function createQuery(): ListQueryDTO
    {
        return ListQueryDTO::fromArray(['page' => 1, 'per_page' => 20]);
    }

    // ─────────────────────────────
    // Global Search Tests
    // ─────────────────────────────

    public function test_sessions_global_search_matches_status(): void
    {
        $query = $this->createQuery();
        $filters = new ResolvedListFilters(
            globalSearch: 'active',
            columnFilters: [],
            dateFrom: null,
            dateTo: null
        );

        $this->expectSqlContains([
            "(CASE\n                WHEN s.is_revoked = 1 THEN 'revoked'\n                WHEN s.expires_at <= NOW() THEN 'expired'\n                ELSE 'active'\n            END) LIKE :global"
        ]);

        $this->reader->getSessions($query, $filters, null, 'hash');
    }

    public function test_sessions_global_search_matches_session_id_using_like(): void
    {
        $query = $this->createQuery();
        $filters = new ResolvedListFilters(
            globalSearch: 'abc',
            columnFilters: [],
            dateFrom: null,
            dateTo: null
        );

        $this->expectSqlContains(['s.session_id LIKE :global']);

        $this->reader->getSessions($query, $filters, null, 'hash');
    }

    public function test_sessions_global_search_matches_admin_id_when_numeric(): void
    {
        $query = $this->createQuery();
        $filters = new ResolvedListFilters(
            globalSearch: '123',
            columnFilters: [],
            dateFrom: null,
            dateTo: null
        );

        $this->expectSqlContains(['s.admin_id = :global_id']);

        $this->reader->getSessions($query, $filters, null, 'hash');
    }

    public function test_sessions_global_search_does_not_match_admin_id_when_non_numeric(): void
    {
        $query = $this->createQuery();
        $filters = new ResolvedListFilters(
            globalSearch: 'not_numeric',
            columnFilters: [],
            dateFrom: null,
            dateTo: null
        );

        $this->expectSqlDoesNotContain(['s.admin_id = :global_id']);

        $this->reader->getSessions($query, $filters, null, 'hash');
    }

    // ─────────────────────────────
    // Column Search Tests
    // ─────────────────────────────

    public function test_sessions_column_search_matches_session_id_using_like(): void
    {
        $query = $this->createQuery();
        $filters = new ResolvedListFilters(
            globalSearch: null,
            columnFilters: ['session_id' => 'abc'],
            dateFrom: null,
            dateTo: null
        );

        $this->expectSqlContains(['s.session_id LIKE :session_id']);

        $this->reader->getSessions($query, $filters, null, 'hash');
    }

    public function test_sessions_column_search_matches_admin_id_exact(): void
    {
        $query = $this->createQuery();
        $filters = new ResolvedListFilters(
            globalSearch: null,
            columnFilters: ['admin_id' => '123'],
            dateFrom: null,
            dateTo: null
        );

        $this->expectSqlContains(['s.admin_id = :search_admin_id']);

        $this->reader->getSessions($query, $filters, null, 'hash');
    }

    public function test_sessions_column_search_matches_status_active(): void
    {
        $query = $this->createQuery();
        $filters = new ResolvedListFilters(
            globalSearch: null,
            columnFilters: ['status' => 'active'],
            dateFrom: null,
            dateTo: null
        );

        // active => not revoked AND not expired
        $this->expectSqlContains(['s.is_revoked = 0 AND s.expires_at > NOW()']);

        $this->reader->getSessions($query, $filters, null, 'hash');
    }

    // ─────────────────────────────
    // Date Filter Tests
    // ─────────────────────────────

    public function test_sessions_applies_date_filter_to_created_at(): void
    {
        $query = $this->createQuery();
        $filters = new ResolvedListFilters(
            globalSearch: null,
            columnFilters: [],
            dateFrom: new \DateTimeImmutable('2023-01-01'),
            dateTo: new \DateTimeImmutable('2023-01-02')
        );

        $this->expectSqlContains([
            's.created_at >= :date_from',
            's.created_at <= :date_to'
        ]);

        $this->reader->getSessions($query, $filters, null, 'hash');
    }

    // ─────────────────────────────
    // Helper
    // ─────────────────────────────

    private function expectSqlContains(array $snippets): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(0);
        $stmt->method('fetchAll')->willReturn([]);

        $totalStmt = $this->createMock(PDOStatement::class);
        $totalStmt->method('fetchColumn')->willReturn(0);
        $this->pdo->method('query')->willReturn($totalStmt);

        $this->pdo->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->callback(function ($sql) use ($snippets) {
                 if (str_contains($sql, 'COUNT(*) FROM admin_sessions') && !str_contains($sql, 'WHERE')) return true; // Total count query (unfiltered)

                 foreach ($snippets as $snippet) {
                     if (!str_contains($sql, $snippet)) {
                         return false;
                     }
                 }
                 return true;
            }))
            ->willReturn($stmt);
    }

    private function expectSqlDoesNotContain(array $snippets): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(0);
        $stmt->method('fetchAll')->willReturn([]);

        $totalStmt = $this->createMock(PDOStatement::class);
        $totalStmt->method('fetchColumn')->willReturn(0);
        $this->pdo->method('query')->willReturn($totalStmt);

        $this->pdo->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->callback(function ($sql) use ($snippets) {
                 if (str_contains($sql, 'COUNT(*) FROM admin_sessions') && !str_contains($sql, 'WHERE')) return true;

                 foreach ($snippets as $snippet) {
                     if (str_contains($sql, $snippet)) {
                         return false;
                     }
                 }
                 return true;
            }))
            ->willReturn($stmt);
    }
}
