<?php

declare(strict_types=1);

namespace Tests\Canonical\Admins;

use Maatify\AdminKernel\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use Maatify\AdminKernel\Domain\DTO\AdminConfigDTO;
use Maatify\AdminKernel\Domain\List\AdminListCapabilities;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\AdminKernel\Infrastructure\Reader\Admin\PdoAdminQueryReader;
use App\Modules\Validation\Schemas\SharedListQuerySchema;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;
use ReflectionClass;

class AdminsQueryContractTest extends TestCase
{
    /**
     * @description Enforce that SharedListQuerySchema rejects forbidden keys
     */
    #[Test]
    public function test_admins_schema_rejects_forbidden_keys(): void
    {
        $schema = new SharedListQuerySchema();

        $forbidden = ['filters', 'limit', 'items', 'meta', 'from_date', 'to_date'];

        foreach ($forbidden as $key) {
            $input = ['page' => 1, 'per_page' => 20, $key => 'value'];
            $result = $schema->validate($input);
            $this->assertFalse($result->isValid(), "Schema should reject forbidden key: {$key}");
            $this->assertArrayHasKey($key, $result->getErrors(), "Schema should report error for key: {$key}");
        }
    }

    /**
     * @description Enforce that SharedListQuerySchema rejects empty search blocks
     */
    #[Test]
    public function test_admins_schema_rejects_empty_search_block(): void
    {
        $schema = new SharedListQuerySchema();

        $input = [
            'page' => 1,
            'per_page' => 20,
            'search' => [] // Empty search block
        ];

        $result = $schema->validate($input);
        $this->assertFalse($result->isValid(), "Schema should reject empty search block");
        $this->assertArrayHasKey('search', $result->getErrors());
    }

    /**
     * @description Enforce that SharedListQuerySchema accepts valid canonical shape
     */
    #[Test]
    public function test_admins_schema_accepts_valid_canonical_shape(): void
    {
        $schema = new SharedListQuerySchema();

        $input = [
            'page' => 1,
            'per_page' => 20,
            'search' => [
                'global' => 'test'
            ],
            'date' => [
                'from' => '2023-01-01',
                'to' => '2023-01-31'
            ]
        ];

        $result = $schema->validate($input);
        $this->assertTrue($result->isValid());
    }

    /**
     * @description Enforce that SharedListQuerySchema rejects partial date
     */
    #[Test]
    public function test_admins_schema_rejects_partial_date(): void
    {
        $schema = new SharedListQuerySchema();

        $inputs = [
            ['page' => 1, 'per_page' => 20, 'date' => ['from' => '2023-01-01']],
            ['page' => 1, 'per_page' => 20, 'date' => ['to' => '2023-01-31']]
        ];

        foreach ($inputs as $input) {
            $result = $schema->validate($input);
            $this->assertFalse($result->isValid(), "Schema should reject partial date");
            $this->assertArrayHasKey('date', $result->getErrors());
        }
    }

    /**
     * @description Enforce DTO normalization (defaults and optional blocks)
     */
    #[Test]
    public function test_admins_dto_normalization(): void
    {
        $input = ['page' => 2]; // Minimal input
        $dto = ListQueryDTO::fromArray($input);

        $this->assertSame(2, $dto->page);
        $this->assertSame(20, $dto->perPage, 'Default per_page should be 20');
        $this->assertNull($dto->globalSearch);
        $this->assertEmpty($dto->columnFilters);
        $this->assertNull($dto->dateFrom);
        $this->assertNull($dto->dateTo);

        $inputFull = [
            'page' => 3,
            'per_page' => 50,
            'search' => ['global' => ' query '], // Should trim
            'date' => ['from' => '2023-01-01', 'to' => '2023-01-31']
        ];
        $dtoFull = ListQueryDTO::fromArray($inputFull);

        $this->assertSame(3, $dtoFull->page);
        $this->assertSame(50, $dtoFull->perPage);
        $this->assertSame('query', $dtoFull->globalSearch);
        $this->assertEquals(new DateTimeImmutable('2023-01-01'), $dtoFull->dateFrom);
        $this->assertEquals(new DateTimeImmutable('2023-01-31'), $dtoFull->dateTo);
    }

    /**
     * @description Resolver allows ONLY aliases defined in AdminListCapabilities
     */
    #[Test]
    public function test_admins_resolver_enforces_aliases(): void
    {
        $capabilities = AdminListCapabilities::define();
        $resolver = new ListFilterResolver();

        // Valid aliases
        $dto = ListQueryDTO::fromArray([
            'search' => [
                'columns' => [
                    'id' => '123',
                    'email' => 'admin@example.com'
                ]
            ]
        ]);
        $resolved = $resolver->resolve($dto, $capabilities);
        $this->assertArrayHasKey('id', $resolved->columnFilters);
        $this->assertArrayHasKey('email', $resolved->columnFilters);

        // Invalid aliases (real column names)
        $dtoInvalid = ListQueryDTO::fromArray([
            'search' => [
                'columns' => [
                    'email_encrypted' => 'something',
                    'email_blind_index' => 'something',
                    'created_at' => '2023-01-01'
                ]
            ]
        ]);
        $resolvedInvalid = $resolver->resolve($dtoInvalid, $capabilities);
        $this->assertEmpty($resolvedInvalid->columnFilters, 'Real column names must be filtered out by resolver');
    }

    /**
     * @description Reader applies Global Search (ID match)
     */
    #[Test]
    public function test_admins_reader_global_search_matches_id(): void
    {
        $pdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);
        $crypto = $this->createMock(AdminIdentifierCryptoServiceInterface::class);

        // Setup DTO and capabilities
        $dto = ListQueryDTO::fromArray(['search' => ['global' => '123']]);
        $capabilities = AdminListCapabilities::define();
        $resolver = new ListFilterResolver();
        $filters = $resolver->resolve($dto, $capabilities);

        // Expectation: Query containing ID check
        $pdo->method('query')->willReturn($this->createMock(PDOStatement::class)); // For total count

        $sqlLog = [];
        $pdo->method('prepare')->willReturnCallback(function ($sql) use ($stmt, &$sqlLog) {
             $sqlLog[] = $sql;
             return $stmt;
        });

        $reader = new PdoAdminQueryReader($pdo, $crypto);

        // Just calling it to trigger the mock callbacks
        $stmt->method('fetchColumn')->willReturn(0);
        $stmt->method('fetchAll')->willReturn([]);

        $reader->queryAdmins($dto, $filters);

        $found = false;
        foreach ($sqlLog as $sql) {
            if (str_contains($sql, 'a.id = :global_id')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Global ID search should add WHERE clause');
    }

    /**
     * @description Reader applies Global Search (Email match via Blind Index)
     */
    #[Test]
    public function test_admins_reader_global_search_matches_email(): void
    {
        $pdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);
        $crypto = $this->createMock(AdminIdentifierCryptoServiceInterface::class);

        $pdo->method('query')->willReturn($this->createMock(PDOStatement::class));
        $stmt->method('fetchColumn')->willReturn(0);
        $stmt->method('fetchAll')->willReturn([]);

        $crypto->method('deriveEmailBlindIndex')->willReturn('blind_index_mock');

        $sqlLog = [];
        $pdo->method('prepare')->willReturnCallback(function ($sql) use ($stmt, &$sqlLog) {
            $sqlLog[] = $sql;
            return $stmt;
        });

        $reader = new PdoAdminQueryReader($pdo, $crypto);

        $dto = ListQueryDTO::fromArray(['search' => ['global' => 'test@example.com']]);
        $capabilities = AdminListCapabilities::define();
        $resolver = new ListFilterResolver();
        $filters = $resolver->resolve($dto, $capabilities);

        $reader->queryAdmins($dto, $filters);

        $found = false;
        foreach ($sqlLog as $sql) {
            if (str_contains($sql, 'ae.email_blind_index = :global_email')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Global email search should trigger blind index lookup');
    }

    /**
     * @description Reader ignores Global Search if not ID and not Email
     */
    #[Test]
    public function test_admins_reader_ignores_invalid_global_search(): void
    {
        $pdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);
        $crypto = $this->createMock(AdminIdentifierCryptoServiceInterface::class);

        $pdo->method('query')->willReturn($this->createMock(PDOStatement::class));
        $stmt->method('fetchColumn')->willReturn(0);
        $stmt->method('fetchAll')->willReturn([]);

        $sqlLog = [];
        $pdo->method('prepare')->willReturnCallback(function ($sql) use ($stmt, &$sqlLog) {
            $sqlLog[] = $sql;
            return $stmt;
        });

        $reader = new PdoAdminQueryReader($pdo, $crypto);

        $dto = ListQueryDTO::fromArray(['search' => ['global' => 'alice']]); // Not an ID, Not an Email
        $capabilities = AdminListCapabilities::define();
        $resolver = new ListFilterResolver();
        $filters = $resolver->resolve($dto, $capabilities);

        $reader->queryAdmins($dto, $filters);

        $found = false;
        foreach ($sqlLog as $sql) {
            if (str_contains($sql, ':global_id') || str_contains($sql, ':global_email')) {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found, 'Invalid global search term should be ignored (no WHERE clause added)');
    }

    /**
     * @description Reader applies Column Search (ID)
     */
    #[Test]
    public function test_admins_reader_accepts_id_column_search(): void
    {
        $pdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);
        $crypto = $this->createMock(AdminIdentifierCryptoServiceInterface::class);

        $pdo->method('query')->willReturn($this->createMock(PDOStatement::class));
        $stmt->method('fetchColumn')->willReturn(0);
        $stmt->method('fetchAll')->willReturn([]);

        $sqlLog = [];
        $pdo->method('prepare')->willReturnCallback(function ($sql) use ($stmt, &$sqlLog) {
            $sqlLog[] = $sql;
            return $stmt;
        });

        $reader = new PdoAdminQueryReader($pdo, $crypto);

        $dto = ListQueryDTO::fromArray(['search' => ['columns' => ['id' => '123']]]);
        $capabilities = AdminListCapabilities::define();
        $resolver = new ListFilterResolver();
        $filters = $resolver->resolve($dto, $capabilities);

        $reader->queryAdmins($dto, $filters);

        $found = false;
        foreach ($sqlLog as $sql) {
            if (str_contains($sql, 'a.id = :admin_id')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Column ID search should add WHERE clause');
    }

    /**
     * @description Reader applies Column Search (Email)
     */
    #[Test]
    public function test_admins_reader_accepts_email_column_search(): void
    {
        $pdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);
        $crypto = $this->createMock(AdminIdentifierCryptoServiceInterface::class);

        $pdo->method('query')->willReturn($this->createMock(PDOStatement::class));
        $stmt->method('fetchColumn')->willReturn(0);
        $stmt->method('fetchAll')->willReturn([]);

        $crypto->method('deriveEmailBlindIndex')->willReturn('blind_index_mock');

        $sqlLog = [];
        $pdo->method('prepare')->willReturnCallback(function ($sql) use ($stmt, &$sqlLog) {
            $sqlLog[] = $sql;
            return $stmt;
        });

        $reader = new PdoAdminQueryReader($pdo, $crypto);

        $dto = ListQueryDTO::fromArray(['search' => ['columns' => ['email' => 'test@example.com']]]);
        $capabilities = AdminListCapabilities::define();
        $resolver = new ListFilterResolver();
        $filters = $resolver->resolve($dto, $capabilities);

        $reader->queryAdmins($dto, $filters);

        $found = false;
        foreach ($sqlLog as $sql) {
            if (str_contains($sql, 'ae.email_blind_index = :email')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Column Email search should trigger blind index lookup');
    }

    /**
     * @description Reader applies Date Filter
     */
    #[Test]
    public function test_admins_reader_accepts_date_filter(): void
    {
        $pdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);
        $crypto = $this->createMock(AdminIdentifierCryptoServiceInterface::class);

        $pdo->method('query')->willReturn($this->createMock(PDOStatement::class));
        $stmt->method('fetchColumn')->willReturn(0);
        $stmt->method('fetchAll')->willReturn([]);

        $sqlLog = [];
        $pdo->method('prepare')->willReturnCallback(function ($sql) use ($stmt, &$sqlLog) {
            $sqlLog[] = $sql;
            return $stmt;
        });

        $reader = new PdoAdminQueryReader($pdo, $crypto);

        $dto = ListQueryDTO::fromArray(['date' => ['from' => '2023-01-01', 'to' => '2023-01-31']]);
        $capabilities = AdminListCapabilities::define();
        $resolver = new ListFilterResolver();
        $filters = $resolver->resolve($dto, $capabilities);

        $reader->queryAdmins($dto, $filters);

        $foundFrom = false;
        $foundTo = false;
        foreach ($sqlLog as $sql) {
            if (str_contains($sql, 'a.created_at >= :date_from')) $foundFrom = true;
            if (str_contains($sql, 'a.created_at <= :date_to')) $foundTo = true;
        }
        $this->assertTrue($foundFrom && $foundTo, 'Date filter should add range WHERE clause');
    }
}
