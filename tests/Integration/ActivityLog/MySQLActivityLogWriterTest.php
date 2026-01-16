<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 20:09
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Integration\ActivityLog;

use App\Modules\ActivityLog\Drivers\MySQL\MySQLActivityLogWriter;
use App\Modules\ActivityLog\DTO\ActivityLogDTO;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;

final class MySQLActivityLogWriterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        MySQLTestHelper::truncate('activity_logs');
    }

    public function test_it_inserts_activity_log_row(): void
    {
        $pdo = MySQLTestHelper::pdo();
        $writer = new MySQLActivityLogWriter($pdo);

        $dto = new ActivityLogDTO(
            action: 'test.insert',
            actorType: 'test',
            actorId: 1,
            entityType: null,
            entityId: null,
            metadata: ['x' => 1],
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
            requestId: 'req-1',
            occurredAt: new DateTimeImmutable(),
        );

        $writer->write($dto);

        $stmt = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action = 'test.insert'");
        $this->assertNotFalse($stmt);

        $count = (int) $stmt->fetchColumn();

        $this->assertSame(1, $count);
    }
}
