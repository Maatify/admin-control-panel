<?php

declare(strict_types=1);

namespace Maatify\Settings\Tests\Admin\Setting\Infrastructure\Repository;

use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;
use Maatify\Settings\Admin\Setting\Infrastructure\Repository\PdoAdminSettingCommandRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoAdminSettingCommandRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoAdminSettingCommandRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createSchema();
        $this->seedData();

        $this->repository = new PdoAdminSettingCommandRepository($this->pdo);
    }

    private function createSchema(): void
    {
        $this->pdo->exec('
            CREATE TABLE `settings` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `setting_key` VARCHAR(64) NOT NULL UNIQUE,
                `setting_value` VARCHAR(255) NOT NULL DEFAULT """",
                `value_type` VARCHAR(16) NOT NULL,
                `is_admin_editable` TINYINT(1) NOT NULL DEFAULT 0,
                `admin_note` TEXT,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    private function seedData(): void
    {
        $this->pdo->exec('
            INSERT INTO `settings` (`setting_key`, `setting_value`, `value_type`, `is_admin_editable`)
            VALUES (\"maintenance\", \"0\", \"bool\", 1)
        ');
    }

    public function testUpdateValueSuccess(): void
    {
        $command = new UpdateSettingValueCommand('maintenance', '1');

        $result = $this->repository->updateValue($command);

        self::assertTrue($result);

        $stmt = $this->pdo->prepare('SELECT `setting_value` FROM `settings` WHERE `setting_key` = :key');
        $stmt->execute(['key' => 'maintenance']);
        $value = $stmt->fetchColumn();

        self::assertSame('1', $value);
    }

    public function testUpdateValueNotFound(): void
    {
        $command = new UpdateSettingValueCommand('unknown_key', 'new_value');

        $result = $this->repository->updateValue($command);

        self::assertFalse($result);
    }

    public function testUpdateValueWithLongString(): void
    {
        $longValue = str_repeat('x', 255);
        $command = new UpdateSettingValueCommand('maintenance', $longValue);

        $result = $this->repository->updateValue($command);

        self::assertTrue($result);

        $stmt = $this->pdo->prepare('SELECT `setting_value` FROM `settings` WHERE `setting_key` = :key');
        $stmt->execute(['key' => 'maintenance']);
        $value = $stmt->fetchColumn();

        self::assertSame($longValue, $value);
    }

    public function testUpdateValueWithSpecialCharacters(): void
    {
        $value = "special'chars\"quotes\\backslash";
        $command = new UpdateSettingValueCommand('maintenance', $value);

        $result = $this->repository->updateValue($command);

        self::assertTrue($result);

        $stmt = $this->pdo->prepare('SELECT `setting_value` FROM `settings` WHERE `setting_key` = :key');
        $stmt->execute(['key' => 'maintenance']);
        $dbValue = $stmt->fetchColumn();

        self::assertSame($value, $dbValue);
    }

    public function testUpdateValueMultipleTimes(): void
    {
        $command1 = new UpdateSettingValueCommand('maintenance', '1');
        $result1 = $this->repository->updateValue($command1);
        self::assertTrue($result1);

        $command2 = new UpdateSettingValueCommand('maintenance', '0');
        $result2 = $this->repository->updateValue($command2);
        self::assertTrue($result2);

        $stmt = $this->pdo->prepare('SELECT `setting_value` FROM `settings` WHERE `setting_key` = :key');
        $stmt->execute(['key' => 'maintenance']);
        $value = $stmt->fetchColumn();

        self::assertSame('0', $value);
    }

    public function testUpdateValueSameValueRowCountZero(): void
    {
        $command = new UpdateSettingValueCommand('maintenance', '0');
        $result = $this->repository->updateValue($command);

        self::assertTrue($result);
    }
}
