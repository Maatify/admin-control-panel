<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Service;

use App\Context\RequestContext;
use App\Domain\Contracts\AdminTotpSecretStoreInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\StepUpGrant;
use App\Domain\DTO\TotpVerificationResultDTO;
use App\Domain\Enum\Scope;
use App\Domain\Enum\SessionState;
use App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use App\Domain\SecurityEvents\Recorder\SecurityEventRecorderInterface;
use App\Domain\Service\RecoveryStateService;
use App\Domain\Service\StepUpService;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StepUpServiceTest extends TestCase
{
    private MockObject|StepUpGrantRepositoryInterface $grantRepository;
    private MockObject|AdminTotpSecretStoreInterface $totpSecretStore;
    private MockObject|TotpServiceInterface $totpService;
    private MockObject|AuthoritativeSecurityAuditWriterInterface $outboxWriter;
    private MockObject|SecurityEventRecorderInterface $securityEventRecorder;
    private MockObject|RecoveryStateService $recoveryState;
    private MockObject|PDO $pdo;
    private StepUpService $service;

    protected function setUp(): void
    {
        $this->grantRepository = $this->createMock(StepUpGrantRepositoryInterface::class);
        $this->totpSecretStore = $this->createMock(AdminTotpSecretStoreInterface::class);
        $this->totpService = $this->createMock(TotpServiceInterface::class);
        $this->outboxWriter = $this->createMock(AuthoritativeSecurityAuditWriterInterface::class);
        $this->securityEventRecorder = $this->createMock(SecurityEventRecorderInterface::class);
        $this->recoveryState = $this->createMock(RecoveryStateService::class);
        $this->pdo = $this->createMock(PDO::class);

        $this->service = new StepUpService(
            $this->grantRepository,
            $this->totpSecretStore,
            $this->totpService,
            $this->outboxWriter,
            $this->securityEventRecorder,
            $this->recoveryState,
            $this->pdo
        );
    }

    public function testVerifyTotpSuccess(): void
    {
        $adminId = 1;
        $token = 'token';
        $code = '123456';
        $context = new RequestContext('req-id', '127.0.0.1', 'user-agent');

        $this->totpSecretStore->method('retrieve')->with($adminId)->willReturn('secret');
        $this->totpService->method('verify')->with('secret', $code)->willReturn(true);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('commit');
        $this->grantRepository->expects($this->once())->method('save');
        $this->outboxWriter->expects($this->once())->method('write');

        $result = $this->service->verifyTotp($adminId, $token, $code, $context);

        $this->assertTrue($result->success);
    }

    public function testVerifyTotpNotEnrolled(): void
    {
        $adminId = 1;
        $token = 'token';
        $code = '123456';
        $context = new RequestContext('req-id', '127.0.0.1', 'user-agent');

        $this->totpSecretStore->method('retrieve')->with($adminId)->willReturn(null);

        $this->securityEventRecorder->expects($this->once())
            ->method('record')
            ->with($this->callback(function (SecurityEventRecordDTO $event) {
                return $event->eventType === SecurityEventTypeEnum::STEP_UP_NOT_ENROLLED;
            }));

        $result = $this->service->verifyTotp($adminId, $token, $code, $context);

        $this->assertFalse($result->success);
        $this->assertEquals('TOTP not enrolled', $result->errorReason);
    }

    public function testVerifyTotpInvalidCode(): void
    {
        $adminId = 1;
        $token = 'token';
        $code = '123456';
        $context = new RequestContext('req-id', '127.0.0.1', 'user-agent');

        $this->totpSecretStore->method('retrieve')->with($adminId)->willReturn('secret');
        $this->totpService->method('verify')->with('secret', $code)->willReturn(false);

        $this->securityEventRecorder->expects($this->once())
            ->method('record')
            ->with($this->callback(function (SecurityEventRecordDTO $event) {
                return $event->eventType === SecurityEventTypeEnum::STEP_UP_INVALID_CODE;
            }));

        $result = $this->service->verifyTotp($adminId, $token, $code, $context);

        $this->assertFalse($result->success);
        $this->assertEquals('Invalid code', $result->errorReason);
    }

    public function testVerifyTotpRollbackOnFailure(): void
    {
        $adminId = 1;
        $token = 'token';
        $code = '123456';
        $context = new RequestContext('req-id', '127.0.0.1', 'user-agent');

        $this->totpSecretStore->method('retrieve')->with($adminId)->willReturn('secret');
        $this->totpService->method('verify')->with('secret', $code)->willReturn(true);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->grantRepository->method('save')->willThrowException(new \Exception('DB Error'));
        $this->pdo->expects($this->once())->method('rollBack');

        $this->expectException(\Exception::class);
        $this->service->verifyTotp($adminId, $token, $code, $context);
    }

    public function testHasGrantActive(): void
    {
        $adminId = 1;
        $token = 'token';
        $scope = Scope::LOGIN;
        $context = new RequestContext('req-id', '127.0.0.1', 'user-agent');
        $sessionId = hash('sha256', $token);
        $riskHash = hash('sha256', $context->ipAddress . '|' . $context->userAgent);

        $grant = new StepUpGrant(
            $adminId,
            $sessionId,
            $scope,
            $riskHash,
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            false
        );

        $this->grantRepository->method('find')->with($adminId, $sessionId, $scope)->willReturn($grant);

        $this->assertTrue($this->service->hasGrant($adminId, $token, $scope, $context));
    }

    public function testHasGrantExpired(): void
    {
        $adminId = 1;
        $token = 'token';
        $scope = Scope::LOGIN;
        $context = new RequestContext('req-id', '127.0.0.1', 'user-agent');
        $sessionId = hash('sha256', $token);
        $riskHash = hash('sha256', $context->ipAddress . '|' . $context->userAgent);

        $grant = new StepUpGrant(
            $adminId,
            $sessionId,
            $scope,
            $riskHash,
            new DateTimeImmutable('-2 hours'),
            new DateTimeImmutable('-1 hour'),
            false
        );

        $this->grantRepository->method('find')->with($adminId, $sessionId, $scope)->willReturn($grant);

        $this->assertFalse($this->service->hasGrant($adminId, $token, $scope, $context));
    }

    public function testHasGrantRiskMismatch(): void
    {
        $adminId = 1;
        $token = 'token';
        $scope = Scope::LOGIN;
        $context = new RequestContext('req-id', '127.0.0.1', 'user-agent');
        $sessionId = hash('sha256', $token);
        // Different risk hash
        $grantRiskHash = 'different-hash';

        $grant = new StepUpGrant(
            $adminId,
            $sessionId,
            $scope,
            $grantRiskHash,
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            false
        );

        $this->grantRepository->method('find')->with($adminId, $sessionId, $scope)->willReturn($grant);

        $this->securityEventRecorder->expects($this->once())
            ->method('record')
            ->with($this->callback(function (SecurityEventRecordDTO $event) {
                return $event->eventType === SecurityEventTypeEnum::STEP_UP_RISK_MISMATCH;
            }));

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->grantRepository->expects($this->once())->method('revoke');
        $this->pdo->expects($this->once())->method('commit');

        $this->assertFalse($this->service->hasGrant($adminId, $token, $scope, $context));
    }

    public function testHasGrantSingleUseConsumed(): void
    {
        $adminId = 1;
        $token = 'token';
        $scope = Scope::LOGIN;
        $context = new RequestContext('req-id', '127.0.0.1', 'user-agent');
        $sessionId = hash('sha256', $token);
        $riskHash = hash('sha256', $context->ipAddress . '|' . $context->userAgent);

        $grant = new StepUpGrant(
            $adminId,
            $sessionId,
            $scope,
            $riskHash,
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            true // Single use
        );

        $this->grantRepository->method('find')->with($adminId, $sessionId, $scope)->willReturn($grant);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->grantRepository->expects($this->once())->method('revoke');
        $this->outboxWriter->expects($this->once())->method('write');
        $this->pdo->expects($this->once())->method('commit');

        $this->assertTrue($this->service->hasGrant($adminId, $token, $scope, $context));
    }

    public function testGetSessionStateActive(): void
    {
        $adminId = 1;
        $token = 'token';
        $context = new RequestContext('req-id', '127.0.0.1', 'user-agent');
        $sessionId = hash('sha256', $token);
        $riskHash = hash('sha256', $context->ipAddress . '|' . $context->userAgent);

        $grant = new StepUpGrant(
            $adminId,
            $sessionId,
            Scope::LOGIN,
            $riskHash,
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 hour'),
            false
        );

        $this->grantRepository->method('find')->with($adminId, $sessionId, Scope::LOGIN)->willReturn($grant);

        $this->assertEquals(SessionState::ACTIVE, $this->service->getSessionState($adminId, $token, $context));
    }

    public function testGetSessionStatePending(): void
    {
        $adminId = 1;
        $token = 'token';
        $context = new RequestContext('req-id', '127.0.0.1', 'user-agent');
        $sessionId = hash('sha256', $token);

        $this->grantRepository->method('find')->with($adminId, $sessionId, Scope::LOGIN)->willReturn(null);

        $this->assertEquals(SessionState::PENDING_STEP_UP, $this->service->getSessionState($adminId, $token, $context));
    }

    public function testLogDenial(): void
    {
        $adminId = 1;
        $token = 'token';
        $scope = Scope::LOGIN;
        $context = new RequestContext('req-id', '127.0.0.1', 'user-agent');

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->outboxWriter->expects($this->once())->method('write')
             ->with($this->callback(function (AuditEventDTO $event) {
                 return $event->action === 'stepup_denied';
             }));
        $this->pdo->expects($this->once())->method('commit');

        $this->service->logDenial($adminId, $token, $scope, $context);
    }
}
