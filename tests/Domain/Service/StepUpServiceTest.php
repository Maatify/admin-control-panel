<?php

declare(strict_types=1);

namespace Tests\Domain\Service;

use App\Context\RequestContext;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\Contracts\AdminTotpSecretStoreInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\DTO\StepUpGrant;
use App\Domain\Enum\Scope;
use App\Domain\Service\RecoveryStateService;
use App\Domain\Service\StepUpService;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StepUpServiceTest extends TestCase
{
    private StepUpGrantRepositoryInterface&MockObject $grantRepository;
    private AdminTotpSecretStoreInterface&MockObject $totpSecretStore;
    private TotpServiceInterface&MockObject $totpService;
    private AuthoritativeSecurityAuditWriterInterface&MockObject $outboxWriter;
    private RecoveryStateService&MockObject $recoveryState;
    private PDO&MockObject $pdo;

    private StepUpService $service;

    protected function setUp(): void
    {
        $this->grantRepository = $this->createMock(StepUpGrantRepositoryInterface::class);
        $this->totpSecretStore = $this->createMock(AdminTotpSecretStoreInterface::class);
        $this->totpService = $this->createMock(TotpServiceInterface::class);
        $this->outboxWriter = $this->createMock(AuthoritativeSecurityAuditWriterInterface::class);
        $this->recoveryState = $this->createMock(RecoveryStateService::class);
        $this->pdo = $this->createMock(PDO::class);

        // PDO transaction stubs
        $this->pdo->method('beginTransaction')->willReturn(true);
        $this->pdo->method('commit')->willReturn(true);
        $this->pdo->method('rollBack')->willReturn(true);

        $this->service = new StepUpService(
            $this->grantRepository,
            $this->totpSecretStore,
            $this->totpService,
            $this->outboxWriter,
            $this->recoveryState,
            $this->pdo
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
        $sessionId = 'session123';
        // StepUpService logic: $sessionId = hash('sha256', $token);
        // So the grant saved will have the hashed token as session ID.
        // The test was passing 'session123' as token, so let's keep consistent.

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

        $this->outboxWriter->expects($this->once())
            ->method('write');

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

        $this->outboxWriter->expects($this->once())
            ->method('write');

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

        // Security logging removed from test expectation as recorder dependency is removed

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

        // Security logging removed from test expectation as recorder dependency is removed

        $result = $this->service->verifyTotp(1, 'token', 'c', $context);
        $this->assertFalse($result->success);
    }

    public function testHasGrantReturnsTrueForValidGrant(): void
    {
        $context = $this->createRequestContext();
        // hash of 'token'
        $token = 'token';
        $sessionId = hash('sha256', $token);

        // Mock getRiskHash
        // The service uses hash('sha256', $ip . '|' . $ua);
        $riskHash = hash('sha256', $context->ipAddress . '|' . $context->userAgent);

        $grant = new StepUpGrant(
            1, $sessionId, Scope::SECURITY,
            $riskHash,
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            false
        );

        $this->grantRepository->method('find')->willReturn($grant);

        $this->assertTrue($this->service->hasGrant(1, $token, Scope::SECURITY, $context));
    }

    public function testHasGrantReturnsFalseForExpiredGrant(): void
    {
        $context = $this->createRequestContext();
        $token = 'token';
        $sessionId = hash('sha256', $token);
        $riskHash = hash('sha256', $context->ipAddress . '|' . $context->userAgent);

        $grant = new StepUpGrant(
            1, $sessionId, Scope::SECURITY,
            $riskHash,
            new DateTimeImmutable('-2 hours'),
            new DateTimeImmutable('-1 hour'),
            false
        );

        $this->grantRepository->method('find')->willReturn($grant);

        $this->assertFalse($this->service->hasGrant(1, $token, Scope::SECURITY, $context));
    }
}
