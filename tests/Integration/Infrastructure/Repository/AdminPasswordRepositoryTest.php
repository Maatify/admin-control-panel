<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repository;

use App\Domain\DTO\AdminPasswordRecordDTO;
use App\Infrastructure\Repository\AdminPasswordRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;
use Tests\Support\TestKernelFactory;

class AdminPasswordRepositoryTest extends TestCase
{
    private PDO $pdo;
    private AdminPasswordRepository $repository;

    protected function setUp(): void
    {
        // Initialize helper with config
        MySQLTestHelper::init(TestKernelFactory::getTestConfig());

        // Default to real PDO (SQLite) from Helper
        $this->pdo = MySQLTestHelper::pdo();

        MySQLTestHelper::truncate('admin_passwords');

        // Insert dummy admins to satisfy FK constraints
        $this->pdo->exec("INSERT IGNORE INTO admins (id, display_name, status) VALUES (1, 'Admin1', 'ACTIVE')");
        $this->pdo->exec("INSERT IGNORE INTO admins (id, display_name, status) VALUES (2, 'Admin2', 'ACTIVE')");

        $this->repository = new AdminPasswordRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        MySQLTestHelper::truncate('admin_passwords');
    }

    public function test_save_password_executes_correct_sql(): void
    {
        // Mock PDO for this test to check SQL string (because of MySQL specific syntax ON DUPLICATE KEY UPDATE)
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(\PDOStatement::class);

        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->callback(function (string $sql) {
                return str_contains($sql, 'INSERT INTO admin_passwords')
                    && str_contains($sql, 'ON DUPLICATE KEY UPDATE')
                    && str_contains($sql, 'must_change_password = VALUES(must_change_password)');
            }))
            ->willReturn($stmtMock);

        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                123,
                'hash_value',
                'pepper_v1',
                1 // true casts to 1
            ]);

        $repo = new AdminPasswordRepository($pdoMock);
        $repo->savePassword(123, 'hash_value', 'pepper_v1', true);
    }

    public function test_save_password_handles_false_flag_correctly(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(\PDOStatement::class);

        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                456,
                'new_hash',
                'pepper_v2',
                0 // false casts to 0
            ]);

        $repo = new AdminPasswordRepository($pdoMock);
        $repo->savePassword(456, 'new_hash', 'pepper_v2', false);
    }

    public function test_get_password_record_returns_dto_with_must_change_password_true(): void
    {
        // Use real SQLite PDO
        $this->pdo->exec("INSERT INTO admin_passwords (admin_id, password_hash, pepper_id, must_change_password) VALUES (1, 'hash1', 'pepper1', 1)");

        $record = $this->repository->getPasswordRecord(1);

        $this->assertInstanceOf(AdminPasswordRecordDTO::class, $record);
        $this->assertSame('hash1', $record->hash);
        $this->assertSame('pepper1', $record->pepperId);
        $this->assertTrue($record->mustChangePassword);
    }

    public function test_get_password_record_returns_dto_with_must_change_password_false(): void
    {
        // Use real SQLite PDO
        $this->pdo->exec("INSERT INTO admin_passwords (admin_id, password_hash, pepper_id, must_change_password) VALUES (2, 'hash2', 'pepper2', 0)");

        $record = $this->repository->getPasswordRecord(2);

        $this->assertInstanceOf(AdminPasswordRecordDTO::class, $record);
        $this->assertFalse($record->mustChangePassword);
    }

    public function test_get_password_record_returns_null_if_not_found(): void
    {
        $record = $this->repository->getPasswordRecord(999);
        $this->assertNull($record);
    }
}
