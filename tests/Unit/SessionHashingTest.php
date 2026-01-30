<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Repository\AdminSessionRepository;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionHashingTest extends TestCase
{
    private PDO&MockObject $pdo;
    private ClockInterface&MockObject $clock;
    private AdminSessionRepository $repository;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->clock = $this->createMock(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $this->repository = new AdminSessionRepository($this->pdo, $this->clock);
    }

    public function testCreateSessionStoresHash(): void
    {
        $adminId = 123;
        $stmt = $this->createMock(PDOStatement::class);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $stmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $params) use ($adminId) {
                // $params[0] should be the hash (64 chars hex SHA256)
                // $params[1] should be adminId
                return isset($params[0]) && is_string($params[0]) && strlen($params[0]) === 64
                    && isset($params[1]) && $params[1] === $adminId;
            }));

        $token = $this->repository->createSession($adminId);

        // Token should be 64 chars (32 bytes hex)
        $this->assertEquals(64, strlen($token));
    }

    public function testFindSessionHashesToken(): void
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);

        $stmt = $this->createMock(PDOStatement::class);
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $stmt->expects($this->once())
            ->method('execute')
            ->with([$hash]);

        $stmt->method('fetch')
            ->willReturn([
                'admin_id' => '1',
                'expires_at' => '2024-01-01 00:00:00',
                'is_revoked' => '0'
            ]);

        $result = $this->repository->findSession($token);
        $this->assertNotNull($result);
        $this->assertEquals(1, $result['admin_id']);
    }
}
