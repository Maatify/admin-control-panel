<?php

declare(strict_types=1);

namespace Maatify\Settings\Tests\Admin\Setting\Infrastructure\Repository;

use Maatify\Settings\Admin\Setting\Infrastructure\Repository\PdoAdminSettingQueryRepository;
use Maatify\Settings\Shared\DTO\SettingDTO;
use Maatify\Settings\Shared\DTO\SettingListItemDTO;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoAdminSettingQueryRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoAdminSettingQueryRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createSchema();
        $this->seedData();

        $this->repository = new PdoAdminSettingQueryRepository($this->pdo);
    }

    private function createSchema(): void
    {
        $this->pdo->exec('
            CREATE TABLE `settings` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `setting_key` VARCHAR(64) NOT NULL UNIQUE,
                `setting_value` VARCHAR(255) NOT NULL DEFAULT \'\',
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
            INSERT INTO `settings` (`setting_key`, `setting_value`, `value_type`, `is_admin_editable`, `admin_note`)
            VALUES
                (\'maintenance\', \'0\', \'bool\', 1, \'App maintenance mode\'),
                (\'default_currency\', \'1\', \'int\', 1, \'Default currency id\'),
                (\'system_id\', \'123\', \'int\', 0, \'System identifier\'),
                (\'app_name\', \'MyApp\', \'string\', 1, \'Application name\')
        ');
    }

    public function testFindByKeyFound(): void
    {
        $result = $this->repository->findByKey('maintenance');

        self::assertInstanceOf(SettingDTO::class, $result);
        self::assertSame('maintenance', $result->settingKey);
        self::assertSame('0', $result->settingValue);
        self::assertSame('bool', $result->valueType);
        self::assertTrue($result->isAdminEditable);
    }

    public function testFindByKeyNotFound(): void
    {
        $result = $this->repository->findByKey('unknown_key');

        self::assertNull($result);
    }

    public function testFindByKeyNonEditable(): void
    {
        $result = $this->repository->findByKey('system_id');

        self::assertInstanceOf(SettingDTO::class, $result);
        self::assertFalse($result->isAdminEditable);
    }

    public function testListAllSettings(): void
    {
        $result = $this->repository->list(1, 10, null, []);

        self::assertArrayHasKey('data', $result);
        self::assertArrayHasKey('pagination', $result);
        self::assertCount(4, $result['data']);
        self::assertSame(4, $result['pagination']['total']);
        self::assertSame(4, $result['pagination']['filtered']);
    }

    public function testListWithGlobalSearch(): void
    {
        $result = $this->repository->list(1, 10, 'maintenance', []);

        self::assertCount(1, $result['data']);
        self::assertSame('maintenance', $result['data'][0]->settingKey);
        self::assertSame(1, $result['pagination']['filtered']);
    }

    public function testListWithAdminEditableFilter(): void
    {
        $result = $this->repository->list(1, 10, null, ['is_admin_editable' => 1]);

        self::assertCount(3, $result['data']);
        self::assertSame(4, $result['pagination']['total']);
        self::assertSame(3, $result['pagination']['filtered']);
    }

    public function testListWithValueTypeFilter(): void
    {
        $result = $this->repository->list(1, 10, null, ['value_type' => 'int']);

        self::assertCount(2, $result['data']);
        self::assertSame(2, $result['pagination']['filtered']);
    }

    public function testListPagination(): void
    {
        $result = $this->repository->list(1, 2, null, []);

        self::assertCount(2, $result['data']);
        self::assertSame(1, $result['pagination']['page']);
        self::assertSame(2, $result['pagination']['per_page']);
        self::assertSame(4, $result['pagination']['total']);
        self::assertSame(4, $result['pagination']['filtered']);
    }

    public function testListSecondPage(): void
    {
        $result = $this->repository->list(2, 2, null, []);

        self::assertCount(2, $result['data']);
        self::assertSame(2, $result['pagination']['page']);
    }

    public function testListAsKeyValue(): void
    {
        $result = $this->repository->listAsKeyValue();

        self::assertIsArray($result);
        self::assertSame('0', $result['maintenance']);
        self::assertSame('1', $result['default_currency']);
        self::assertSame('123', $result['system_id']);
        self::assertSame('MyApp', $result['app_name']);
    }
}
