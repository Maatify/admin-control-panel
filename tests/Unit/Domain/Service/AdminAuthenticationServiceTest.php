<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Service;

use App\Context\RequestContext;
use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\DTO\AdminEmailIdentifierDTO;
use App\Domain\DTO\AdminLoginResultDTO;
use App\Domain\Admin\Enum\AdminStatusEnum;
use App\Domain\DTO\AdminPasswordRecordDTO;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Exception\MustChangePasswordException;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\PasswordService;
use App\Domain\Service\RecoveryStateService;
use App\Infrastructure\Repository\AdminRepository;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AdminAuthenticationServiceTest extends TestCase
{
    private AdminIdentifierLookupInterface&MockObject $lookup;
    private AdminPasswordRepositoryInterface&MockObject $passwordRepo;
    private AdminSessionRepositoryInterface&MockObject $sessionRepo;
    private RecoveryStateService&MockObject $recovery;
    private PDO&MockObject $pdo;
    private PasswordService&MockObject $passwordService;
    private AdminRepository&MockObject $adminRepository;

    private AdminAuthenticationService $service;

    protected function setUp(): void
    {
        $this->lookup = $this->createMock(AdminIdentifierLookupInterface::class);
        $this->passwordRepo = $this->createMock(AdminPasswordRepositoryInterface::class);
        $this->sessionRepo = $this->createMock(AdminSessionRepositoryInterface::class);
        $this->recovery = $this->createMock(RecoveryStateService::class);
        $this->pdo = $this->createMock(PDO::class);
        $this->passwordService = $this->createMock(PasswordService::class);
        $this->adminRepository = $this->createMock(AdminRepository::class);

        $this->service = new AdminAuthenticationService(
            $this->lookup,
            $this->passwordRepo,
            $this->sessionRepo,
            $this->recovery,
            $this->pdo,
            $this->passwordService,
            $this->adminRepository
        );
    }

    public function test_login_succeeds_when_must_change_password_is_false(): void
    {
        $blindIndex = 'blind_index';
        $password = 'password';
        $adminId = 123;
        $context = new RequestContext('req-id', '127.0.0.1', 'agent');

        $this->lookup->method('findByBlindIndex')->with($blindIndex)->willReturn(
            new AdminEmailIdentifierDTO(1, $adminId, VerificationStatus::VERIFIED)
        );

        $record = new AdminPasswordRecordDTO('hash', 'pepper', false);
        $this->passwordRepo->method('getPasswordRecord')->with($adminId)->willReturn($record);

        $this->passwordService->method('verify')->with($password, 'hash', 'pepper')->willReturn(true);
        $this->passwordService->method('needsRehash')->willReturn(false);

        $this->adminRepository->method('getStatus')->with($adminId)->willReturn(AdminStatusEnum::ACTIVE);

        $this->sessionRepo->expects($this->once())->method('createSession')->with($adminId)->willReturn('token_123');
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('commit');

        $result = $this->service->login($blindIndex, $password, $context);

        $this->assertInstanceOf(AdminLoginResultDTO::class, $result);
        $this->assertSame($adminId, $result->adminId);
        $this->assertSame('token_123', $result->token);
    }

    public function test_login_fails_when_must_change_password_is_true(): void
    {
        $blindIndex = 'blind_index';
        $password = 'password';
        $adminId = 123;
        $context = new RequestContext('req-id', '127.0.0.1', 'agent');

        $this->lookup->method('findByBlindIndex')->willReturn(
            new AdminEmailIdentifierDTO(1, $adminId, VerificationStatus::VERIFIED)
        );

        // mustChangePassword = true
        $record = new AdminPasswordRecordDTO('hash', 'pepper', true);
        $this->passwordRepo->method('getPasswordRecord')->with($adminId)->willReturn($record);

        $this->passwordService->method('verify')->with($password, 'hash', 'pepper')->willReturn(true);

        $this->adminRepository->method('getStatus')->with($adminId)->willReturn(AdminStatusEnum::ACTIVE);

        // Assert no session created and no transaction started
        $this->sessionRepo->expects($this->never())->method('createSession');
        $this->pdo->expects($this->never())->method('beginTransaction');

        $this->expectException(MustChangePasswordException::class);
        $this->expectExceptionMessage('Password change required.');

        $this->service->login($blindIndex, $password, $context);
    }
}
