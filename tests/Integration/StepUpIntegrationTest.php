<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Context\RequestContext;
use App\Domain\Contracts\AdminTotpSecretStoreInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\Enum\Scope;
use App\Domain\Enum\SessionState;
use App\Domain\SecurityEvents\Recorder\SecurityEventRecorderInterface;
use App\Domain\Service\RecoveryStateService;
use App\Domain\Service\StepUpService;
use App\Infrastructure\Repository\PdoStepUpGrantRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\MySQLTestHelper;
use PDO;

class StepUpIntegrationTest extends TestCase
{
    private PDO $pdo;
    private StepUpService $service;
    private StepUpGrantRepositoryInterface $grantRepository;
    private $totpSecretStore;
    private $totpService;
    private $outboxWriter;
    private $securityEventRecorder;
    private $recoveryState;

    protected function setUp(): void
    {
        $this->pdo = MySQLTestHelper::pdo();

        // Truncate tables to ensure clean state
        MySQLTestHelper::truncate('step_up_grants');
        MySQLTestHelper::truncate('admin_sessions');
        MySQLTestHelper::truncate('admins');

        // Setup dependencies
        $this->grantRepository = new PdoStepUpGrantRepository($this->pdo);
        $this->totpSecretStore = $this->createMock(AdminTotpSecretStoreInterface::class);
        $this->totpService = $this->createMock(TotpServiceInterface::class);
        $this->outboxWriter = $this->createMock(AuthoritativeSecurityAuditWriterInterface::class);
        $this->securityEventRecorder = $this->createMock(SecurityEventRecorderInterface::class);
        $this->recoveryState = $this->createMock(RecoveryStateService::class);

        $this->service = new StepUpService(
            $this->grantRepository,
            $this->totpSecretStore,
            $this->totpService,
            $this->outboxWriter,
            $this->securityEventRecorder,
            $this->recoveryState,
            $this->pdo
        );

        // Seed data
        $this->seedData();
    }

    private function seedData(): void
    {
        // Create Admin
        $this->pdo->exec("INSERT INTO admins (id) VALUES (1)");

        // Create Session
        $sessionId = hash('sha256', 'session123');
        $this->pdo->exec("INSERT INTO admin_sessions (session_id, admin_id, expires_at) VALUES ('$sessionId', 1, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
    }

    public function testLoginToPendingToActiveFlow(): void
    {
        $adminId = 1;
        $token = 'session123';
        $context = new RequestContext('req1', '127.0.0.1', 'ua');

        // 1. Check initial state: PENDING_STEP_UP
        $state = $this->service->getSessionState($adminId, $token, $context);
        $this->assertEquals(SessionState::PENDING_STEP_UP, $state);

        // 2. Verify TOTP (Mock secret and verification)
        $this->totpSecretStore->method('retrieve')->with($adminId)->willReturn('secret');
        $this->totpService->method('verify')->with('secret', '123456')->willReturn(true);

        $result = $this->service->verifyTotp($adminId, $token, '123456', $context);
        $this->assertTrue($result->success);

        // 3. Check state after verification: ACTIVE
        $state = $this->service->getSessionState($adminId, $token, $context);
        $this->assertEquals(SessionState::ACTIVE, $state);

        // 4. Check DB persistence
        $grant = $this->grantRepository->find($adminId, hash('sha256', $token), Scope::LOGIN);
        $this->assertNotNull($grant);
        $this->assertEquals(Scope::LOGIN, $grant->scope);
    }

    public function testGrantRevokedOnRiskMismatch(): void
    {
        $adminId = 1;
        $token = 'session123';
        $context = new RequestContext('req1', '127.0.0.1', 'ua');

        // Issue grant first
        $this->totpSecretStore->method('retrieve')->willReturn('secret');
        $this->totpService->method('verify')->willReturn(true);
        $this->service->verifyTotp($adminId, $token, '123456', $context);

        // Verify Active
        $this->assertEquals(SessionState::ACTIVE, $this->service->getSessionState($adminId, $token, $context));

        // Change context (different IP/UA) -> Risk Mismatch
        $newContext = new RequestContext('req2', '10.0.0.1', 'ua');

        // This should return PENDING_STEP_UP because getSessionState checks risk
        // and returns PENDING_STEP_UP on mismatch (without revoking).
        $state = $this->service->getSessionState($adminId, $token, $newContext);
        $this->assertEquals(SessionState::PENDING_STEP_UP, $state);

        // Calling hasGrant DOES revoke on risk mismatch.
        $hasGrant = $this->service->hasGrant($adminId, $token, Scope::LOGIN, $newContext);
        $this->assertFalse($hasGrant);

        // Verify it was revoked in DB
        $grant = $this->grantRepository->find($adminId, hash('sha256', $token), Scope::LOGIN);
        $this->assertNull($grant);
    }

    public function testGrantExpiration(): void
    {
        $adminId = 1;
        $token = 'session123';
        $context = new RequestContext('req1', '127.0.0.1', 'ua');

        // Manually insert an expired grant
        $sessionIdHash = hash('sha256', $token);
        $riskHash = hash('sha256', '127.0.0.1|ua');

        $this->pdo->exec("INSERT INTO step_up_grants (admin_id, session_id, scope, risk_context_hash, issued_at, expires_at, single_use)
            VALUES (1, '$sessionIdHash', 'login', '$riskHash', NOW(), DATE_SUB(NOW(), INTERVAL 1 MINUTE), 0)");

        // Check state
        $state = $this->service->getSessionState($adminId, $token, $context);
        $this->assertEquals(SessionState::PENDING_STEP_UP, $state); // Because expired

        // hasGrant should return false
        $this->assertFalse($this->service->hasGrant($adminId, $token, Scope::LOGIN, $context));
    }
}
