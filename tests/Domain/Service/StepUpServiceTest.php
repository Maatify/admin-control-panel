<?php

declare(strict_types=1);

namespace Tests\Domain\Service;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\AdminTotpSecretStoreInterface;
use Maatify\AdminKernel\Domain\Contracts\StepUpGrantRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\TotpServiceInterface;
use Maatify\AdminKernel\Domain\DTO\StepUpGrant;
use Maatify\AdminKernel\Domain\Enum\Scope;
use Maatify\AdminKernel\Domain\Service\RecoveryStateService;
use Maatify\AdminKernel\Domain\Service\StepUpService;
use Maatify\SharedCommon\Contracts\ClockInterface;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StepUpServiceTest extends TestCase
{
    private StepUpGrantRepositoryInterface&MockObject $grantRepository;
    private AdminTotpSecretStoreInterface&MockObject $totpSecretStore;
    private TotpServiceInterface&MockObject $totpService;
    private RecoveryStateService&MockObject $recoveryState;
    private PDO&MockObject $pdo;
    private ClockInterface&MockObject $clock;

    private StepUpService $service;

    protected function setUp(): void
    {
        $this->grantRepository = $this->createMock(StepUpGrantRepositoryInterface::class);
        $this->totpSecretStore = $this->createMock(AdminTotpSecretStoreInterface::class);
        $this->totpService = $this->createMock(TotpServiceInterface::class);
        $this->recoveryState = $this->createMock(RecoveryStateService::class);
        $this->pdo = $this->createMock(PDO::class);
        $this->clock = $this->createMock(ClockInterface::class);

        // ✅ Deterministic clock (no real-time, no system TZ)
        $tz = new DateTimeZone('Africa/Cairo');
        $fixedNow = new DateTimeImmutable('2024-01-01 10:00:00', $tz);

        $this->clock->method('getTimezone')->willReturn($tz);
        $this->clock->method('now')->willReturn($fixedNow);

        // PDO transaction stubs
        $this->pdo->method('beginTransaction')->willReturn(true);
        $this->pdo->method('commit')->willReturn(true);
        $this->pdo->method('rollBack')->willReturn(true);

        $this->service = new StepUpService(
            $this->grantRepository,
            $this->totpSecretStore,
            $this->totpService,
            $this->recoveryState,
            $this->pdo,
            $this->clock
        );
    }

    private function createRequestContext(): RequestContext
    {
        return new RequestContext(
            requestId: 'req-123',
            ipAddress: '127.0.0.1',
            userAgent: 'test-agent'
        );
    }

    public function testVerifyTotpIssuesPrimaryGrantWhenValid(): void
    {
        $adminId = 1;

        $token = 'session123_token';
        $expectedSessionId = hash('sha256', $token);

        $code = '123456';
        $secret = 'secret';
        $context = $this->createRequestContext();

        $this->recoveryState->expects($this->once())
            ->method('enforce');

        $this->totpSecretStore->expects($this->once())
            ->method('retrieve')
            ->with($adminId)
            ->willReturn($secret);

        $this->totpService->expects($this->once())
            ->method('verify')
            ->with($secret, $code)
            ->willReturn(true);

        $this->grantRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (StepUpGrant $grant) use ($adminId, $expectedSessionId) {
                return $grant->adminId === $adminId
                       && $grant->sessionId === $expectedSessionId
                       && $grant->scope === Scope::LOGIN;
            }));

        $result = $this->service->verifyTotp($adminId, $token, $code, $context);
        $this->assertTrue($result->success);
    }

    public function testVerifyTotpIssuesScopedGrantWhenRequested(): void
    {
        $adminId = 1;
        $token = 'session123_token';
        $expectedSessionId = hash('sha256', $token);

        $code = '123456';
        $secret = 'secret';
        $scope = Scope::SECURITY;
        $context = $this->createRequestContext();

        $this->recoveryState->expects($this->once())
            ->method('enforce');

        $this->totpSecretStore->expects($this->once())
            ->method('retrieve')
            ->willReturn($secret);

        $this->totpService->expects($this->once())
            ->method('verify')
            ->willReturn(true);

        $this->grantRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (StepUpGrant $grant) use ($adminId, $expectedSessionId, $scope) {
                return $grant->adminId === $adminId
                       && $grant->sessionId === $expectedSessionId
                       && $grant->scope === $scope;
            }));

        $result = $this->service->verifyTotp($adminId, $token, $code, $context, $scope);
        $this->assertTrue($result->success);
    }

    public function testVerifyTotpFailsWhenNotEnrolled(): void
    {
        $context = $this->createRequestContext();

        $this->recoveryState->expects($this->once())
            ->method('enforce');

        $this->totpSecretStore->expects($this->once())
            ->method('retrieve')
            ->willReturn(null);

        $result = $this->service->verifyTotp(1, 'token', 'c', $context);
        $this->assertFalse($result->success);
        $this->assertEquals('TOTP not enrolled', $result->errorReason);
    }

    public function testVerifyTotpFailsWhenInvalidCode(): void
    {
        $context = $this->createRequestContext();

        $this->recoveryState->expects($this->once())
            ->method('enforce');

        $this->totpSecretStore->method('retrieve')->willReturn('secret');
        $this->totpService->method('verify')->willReturn(false);

        $result = $this->service->verifyTotp(1, 'token', 'c', $context);
        $this->assertFalse($result->success);
    }

    public function testHasGrantReturnsTrueForValidGrant(): void
    {
        $context = $this->createRequestContext();
        $token = 'token';
        $sessionId = hash('sha256', $token);

        $riskHash = hash('sha256', $context->ipAddress . '|' . $context->userAgent);

        $grant = new StepUpGrant(
            1,
            $sessionId,
            Scope::SECURITY,
            $riskHash,
            new DateTimeImmutable('2024-01-01 09:00:00', new DateTimeZone('Africa/Cairo')),
            new DateTimeImmutable('2024-01-01 11:00:00', new DateTimeZone('Africa/Cairo')),
            false
        );

        $this->grantRepository->method('find')->willReturn($grant);

        $this->assertTrue(
            $this->service->hasGrant(1, $token, Scope::SECURITY, $context)
        );
    }

    public function testHasGrantReturnsFalseForExpiredGrant(): void
    {
        $context = $this->createRequestContext();
        $token = 'token';
        $sessionId = hash('sha256', $token);

        $riskHash = hash('sha256', $context->ipAddress . '|' . $context->userAgent);

        $grant = new StepUpGrant(
            1,
            $sessionId,
            Scope::SECURITY,
            $riskHash,
            new DateTimeImmutable('2023-12-31 06:00:00', new DateTimeZone('Africa/Cairo')),
            new DateTimeImmutable('2023-12-31 07:00:00', new DateTimeZone('Africa/Cairo')),
            false
        );

        $this->grantRepository->method('find')->willReturn($grant);

        $this->assertFalse(
            $this->service->hasGrant(1, $token, Scope::SECURITY, $context)
        );
    }
}
