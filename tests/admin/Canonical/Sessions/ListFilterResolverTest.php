<?php

declare(strict_types=1);

namespace Tests\Canonical\Sessions;

use Maatify\AdminKernel\Domain\List\ListCapabilities;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use PHPUnit\Framework\TestCase;

class ListFilterResolverTest extends TestCase
{
    private ListFilterResolver $resolver;
    private ListCapabilities $capabilities;

    protected function setUp(): void
    {
        $this->resolver = new ListFilterResolver();

        // Replicating SessionQueryController capabilities
        $this->capabilities = new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['session_id', 'admin_id'],
            supportsColumnFilters: true,
            filterableColumns: [
                'session_id' => 'session_id',
                'status'     => 'status',
                'admin_id'   => 'admin_id',
            ],
            supportsDateFilter: true,
            dateColumn: 'created_at'
        );
    }

    // ─────────────────────────────
    // Alias Acceptance Tests
    // ─────────────────────────────

    public function test_sessions_accepts_session_id_column_search(): void
    {
        $query = ListQueryDTO::fromArray([
            'search' => ['columns' => ['session_id' => 'abc']],
        ]);
        $result = $this->resolver->resolve($query, $this->capabilities);
        $this->assertArrayHasKey('session_id', $result->columnFilters);
    }

    public function test_sessions_accepts_admin_id_column_search(): void
    {
        $query = ListQueryDTO::fromArray([
            'search' => ['columns' => ['admin_id' => '123']],
        ]);
        $result = $this->resolver->resolve($query, $this->capabilities);
        $this->assertArrayHasKey('admin_id', $result->columnFilters);
    }

    public function test_sessions_accepts_status_column_search(): void
    {
        $query = ListQueryDTO::fromArray([
            'search' => ['columns' => ['status' => 'active']],
        ]);
        $result = $this->resolver->resolve($query, $this->capabilities);
        $this->assertArrayHasKey('status', $result->columnFilters);
    }

    // ─────────────────────────────
    // Rejection Tests (Real Columns & Unknowns)
    // ─────────────────────────────

    public function test_sessions_rejects_real_column_name_for_status(): void
    {
        // Real column for status is `is_revoked` or `expires_at` logic
        $query = ListQueryDTO::fromArray([
            'search' => ['columns' => ['is_revoked' => '1']],
        ]);
        $result = $this->resolver->resolve($query, $this->capabilities);
        $this->assertArrayNotHasKey('is_revoked', $result->columnFilters);
    }

    public function test_sessions_rejects_real_column_name_for_date(): void
    {
        // Real column for date is `created_at`
        $query = ListQueryDTO::fromArray([
            'search' => ['columns' => ['created_at' => '2023-01-01']],
        ]);
        $result = $this->resolver->resolve($query, $this->capabilities);
        $this->assertArrayNotHasKey('created_at', $result->columnFilters);
    }

    public function test_sessions_rejects_undefined_alias(): void
    {
        $query = ListQueryDTO::fromArray([
            'search' => ['columns' => ['unknown_alias' => 'val']],
        ]);
        $result = $this->resolver->resolve($query, $this->capabilities);
        $this->assertArrayNotHasKey('unknown_alias', $result->columnFilters);
    }

    // ─────────────────────────────
    // Feature Tests
    // ─────────────────────────────

    public function test_sessions_resolves_global_search_if_supported(): void
    {
        $query = ListQueryDTO::fromArray([
            'search' => ['global' => 'findme'],
        ]);
        $result = $this->resolver->resolve($query, $this->capabilities);
        $this->assertSame('findme', $result->globalSearch);
    }

    public function test_sessions_resolves_date_filter_if_supported(): void
    {
        $query = ListQueryDTO::fromArray([
            'date' => ['from' => '2023-01-01', 'to' => '2023-01-02'],
        ]);
        $result = $this->resolver->resolve($query, $this->capabilities);
        $this->assertNotNull($result->dateFrom);
        $this->assertNotNull($result->dateTo);
    }
}
