<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\Enum\Scope;
use App\Domain\Exception\PermissionDeniedException;
use DateTimeImmutable;
use PDO;

class RoleAssignmentService
{
    public function __construct(
        private RecoveryStateService $recoveryState,
        private StepUpService $stepUpService,
        private StepUpGrantRepositoryInterface $grantRepository,
        private RoleHierarchyComparator $hierarchyComparator,
        private AdminRoleRepositoryInterface $adminRoleRepository,
        private AuthoritativeSecurityAuditWriterInterface $auditWriter,
        private ClientInfoProviderInterface $clientInfoProvider,
        private PDO $pdo
    ) {
    }

    public function assignRole(int $actorId, int $targetAdminId, int $roleId, string $sessionId): void
    {
        // 1. Recovery State Check
        $this->recoveryState->check();

        // 2. Verify Actor != Target (No Self-Assignment)
        if ($actorId === $targetAdminId) {
            $this->logDenial($actorId, $targetAdminId, $roleId, $sessionId, 'self_assignment_forbidden');
            throw new PermissionDeniedException("Self-assignment of roles is forbidden.");
        }

        // 3. Require Step-Up Grant (Scope::SECURITY)
        if (!$this->stepUpService->hasGrant($actorId, $sessionId, Scope::SECURITY)) {
            $this->logDenial($actorId, $targetAdminId, $roleId, $sessionId, 'step_up_required');
            throw new PermissionDeniedException("Step-Up authentication required for role assignment.");
        }

        // 4. Verify Role Hierarchy
        if (!$this->hierarchyComparator->canAssign($actorId, $roleId)) {
            $this->logDenial($actorId, $targetAdminId, $roleId, $sessionId, 'hierarchy_violation');
            throw new PermissionDeniedException("Insufficient privilege to assign this role.");
        }

        $riskHash = $this->getRiskHash();

        $this->pdo->beginTransaction();
        try {
            // 6. Persist Assignment
            $this->adminRoleRepository->assign($targetAdminId, $roleId);

            // 7. Authoritative Audit (Success)
            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                'role_assigned',
                'admin',
                $targetAdminId,
                'CRITICAL',
                [
                    'role_id' => $roleId,
                    'session_id' => $sessionId,
                    'risk_context_hash' => $riskHash
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            // 9. Invalidate Step-Up Grant (Strict usage)
            $this->grantRepository->revoke($actorId, $sessionId, Scope::SECURITY);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function logDenial(int $actorId, int $targetAdminId, int $roleId, string $sessionId, string $reason): void
    {
        try {
            $this->pdo->beginTransaction();
            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                'role_assignment_denied',
                'admin',
                $targetAdminId,
                'CRITICAL',
                [
                    'role_id' => $roleId,
                    'reason' => $reason,
                    'session_id' => $sessionId
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));
            $this->pdo->commit();
        } catch (\Throwable $e) {
            // Best effort logging if DB fails
            $this->pdo->rollBack();
        }
    }

    private function getRiskHash(): string
    {
        $ip = $this->clientInfoProvider->getIpAddress();
        $ua = $this->clientInfoProvider->getUserAgent();
        return hash('sha256', $ip . '|' . $ua);
    }
}
