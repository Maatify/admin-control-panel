<?php

declare(strict_types=1);

namespace Tests\Domain\Service;

use App\Domain\Contracts\AuditLoggerInterface;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\Contracts\TotpSecretRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\DTO\StepUpGrant;
use App\Domain\DTO\TotpVerificationResultDTO;
use App\Domain\Enum\Scope;
use App\Domain\Service\StepUpService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class StepUpServiceTest extends TestCase
{
    private StepUpGrantRepositoryInterface $grantRepository;
    private TotpSecretRepositoryInterface $secretRepository;
    private TotpServiceInterface $totpService;
    private AuditLoggerInterface $auditLogger;
    private StepUpService $service;

    protected function setUp(): void
    {
        $this->grantRepository = $this->createMock(StepUpGrantRepositoryInterface::class);
        $this->secretRepository = $this->createMock(TotpSecretRepositoryInterface::class);
        $this->totpService = $this->createMock(TotpServiceInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);

        $this->service = new StepUpService(
            $this->grantRepository,
            $this->secretRepository,
            $this->totpService,
            $this->auditLogger
        );
    }

    public function testVerifyTotpIssuesPrimaryGrantWhenValid(): void
    {
        $adminId = 1;
        $sessionId = 'session123';
        $code = '123456';
        $secret = 'secret';

        $this->secretRepository->expects($this->once())
            ->method('get')
            ->with($adminId)
            ->willReturn($secret);

        $this->totpService->expects($this->once())
            ->method('verify')
            ->with($secret, $code)
            ->willReturn(true);

        $this->grantRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (StepUpGrant $grant) use ($adminId, $sessionId) {
                return $grant->adminId === $adminId
                    && $grant->sessionId === $sessionId
                    && $grant->scope === Scope::LOGIN;
            }));

        $this->auditLogger->expects($this->once())
            ->method('log');

        $result = $this->service->verifyTotp($adminId, $sessionId, $code);
        $this->assertTrue($result->success);
    }

    public function testVerifyTotpIssuesScopedGrantWhenRequested(): void
    {
        $adminId = 1;
        $sessionId = 'session123';
        $code = '123456';
        $secret = 'secret';
        $scope = Scope::SECURITY;

        $this->secretRepository->expects($this->once())
            ->method('get')
            ->willReturn($secret);

        $this->totpService->expects($this->once())
            ->method('verify')
            ->willReturn(true);

        $this->grantRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (StepUpGrant $grant) use ($adminId, $sessionId, $scope) {
                return $grant->adminId === $adminId
                    && $grant->sessionId === $sessionId
                    && $grant->scope === $scope;
            }));

        $result = $this->service->verifyTotp($adminId, $sessionId, $code, $scope);
        $this->assertTrue($result->success);
    }

    public function testVerifyTotpFailsWhenNotEnrolled(): void
    {
        $this->secretRepository->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->auditLogger->expects($this->once())
            ->method('log'); // Security event

        $result = $this->service->verifyTotp(1, 's', 'c');
        $this->assertFalse($result->success);
        $this->assertEquals('TOTP not enrolled', $result->errorReason);
    }

    public function testVerifyTotpFailsWhenInvalidCode(): void
    {
        $this->secretRepository->method('get')->willReturn('secret');
        $this->totpService->method('verify')->willReturn(false);

        $this->auditLogger->expects($this->once())
            ->method('log'); // Security event

        $result = $this->service->verifyTotp(1, 's', 'c');
        $this->assertFalse($result->success);
    }

    public function testHasGrantReturnsTrueForValidGrant(): void
    {
        $grant = new StepUpGrant(
            1, 's', Scope::SECURITY,
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            false
        );

        $this->grantRepository->method('find')->willReturn($grant);

        $this->assertTrue($this->service->hasGrant(1, 's', Scope::SECURITY));
    }

    public function testHasGrantReturnsFalseForExpiredGrant(): void
    {
        $grant = new StepUpGrant(
            1, 's', Scope::SECURITY,
            new DateTimeImmutable('-2 hours'),
            new DateTimeImmutable('-1 hour'),
            false
        );

        $this->grantRepository->method('find')->willReturn($grant);

        $this->assertFalse($this->service->hasGrant(1, 's', Scope::SECURITY));
    }
}
