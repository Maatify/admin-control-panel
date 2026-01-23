<?php

declare(strict_types=1);

namespace Tests\Modules\SecuritySignals;

use Maatify\SecuritySignals\DTO\SecuritySignalRecordDTO;
use Maatify\SecuritySignals\Infrastructure\Mysql\SecuritySignalsLoggerMysqlRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;
use Ramsey\Uuid\Uuid;

class SecuritySignalsLoggerMysqlRepositoryTest extends TestCase
{
    private SecuritySignalsLoggerMysqlRepository $repository;

    protected function setUp(): void
    {
        MySQLTestHelper::truncate('security_signals');
        $this->repository = new SecuritySignalsLoggerMysqlRepository(MySQLTestHelper::pdo());
    }

    public function test_writes_record(): void
    {
        $dto = new SecuritySignalRecordDTO(
            eventId: Uuid::uuid4()->toString(),
            actorType: 'ADMIN',
            actorId: 1,
            signalType: 'password_changed',
            severity: 'INFO',
            correlationId: null,
            requestId: null,
            routeName: null,
            ipAddress: null,
            userAgent: null,
            metadata: ['foo' => 'bar'],
            occurredAt: new DateTimeImmutable()
        );

        $this->repository->write($dto);

        $pdo = MySQLTestHelper::pdo();
        $stmt = $pdo->query("SELECT * FROM security_signals");
        $rows = $stmt->fetchAll();

        $this->assertCount(1, $rows);
        $this->assertEquals('password_changed', $rows[0]['signal_type']);
        $this->assertEquals('{"foo":"bar"}', $rows[0]['metadata']);
    }
}
