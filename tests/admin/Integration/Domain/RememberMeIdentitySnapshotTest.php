<?php

declare(strict_types=1);

namespace Tests\Integration\Domain;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminSessionRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\RememberMeRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\RememberMeTokenDTO;
use Maatify\AdminKernel\Domain\Service\RememberMeService;
use Maatify\AdminKernel\Infrastructure\Repository\AdminRepository;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\AdminKernel\Domain\DTO\AdminSessionIdentityDTO;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use PDO;

final class RememberMeIdentitySnapshotTest extends TestCase
{
    public function test_identity_snapshot_is_stored_after_auto_login(): void
    {
        $adminId = 10;

        // 🔹 Mock RememberMeRepository
        $rememberRepo = $this->createMock(RememberMeRepositoryInterface::class);
        $rememberRepo->method('findBySelector')
            ->willReturn(
                new RememberMeTokenDTO(
                    selector: 'selector',
                    hashedValidator: hash('sha256', 'validator'),
                    adminId: $adminId,
                    expiresAt: new DateTimeImmutable('+1 day'),
                    userAgentHash: hash('sha256', 'phpunit')
                )
            );

        $rememberRepo->method('save');
        $rememberRepo->method('deleteBySelector');

        // 🔹 Mock SessionRepository
        $sessionRepo = $this->createMock(AdminSessionRepositoryInterface::class);

        $sessionRepo->expects($this->once())
            ->method('createSession')
            ->with($adminId)
            ->willReturn('new_session_token');

        $sessionRepo->expects($this->once())
            ->method('storeSessionIdentityByHash')
            ->with(
                hash('sha256', 'new_session_token'),
                $this->callback(function ($identity) {
                    if (!$identity instanceof AdminSessionIdentityDTO) {
                        return false;
                    }

                    return $identity->displayName === 'Test Admin'
                           && $identity->avatarUrl === null;
                })
            );

        // 🔹 Mock AdminRepository
        $adminRepository = $this->createMock(AdminRepository::class);
        $adminRepository->method('getIdentitySnapshot')
            ->with($adminId)
            ->willReturn(
                new AdminSessionIdentityDTO(
                    displayName: 'Test Admin',
                    avatarUrl: null
                )
            );

        // 🔹 Mock Clock
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable());

        // 🔹 PDO mock
        $pdo = $this->createMock(PDO::class);
        $pdo->method('beginTransaction');
        $pdo->method('commit');
        $pdo->method('rollBack');
        $pdo->method('inTransaction')->willReturn(false);

        $service = new RememberMeService(
            $rememberRepo,
            $sessionRepo,
            $adminRepository,
            $pdo,
            $clock
        );

        $context = new RequestContext('req1', '127.0.0.1', 'phpunit');

        $result = $service->processAutoLogin('selector:validator', $context);

        $this->assertArrayHasKey('session_token', $result);
        $this->assertArrayHasKey('remember_me_token', $result);
    }
}
