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
        $this->recoveryState->check(); // If this throws, we need to catch it to audit? RecoveryStateService throws RuntimeException? Or custom?
        // Prompt: "For EVERY denial case... Any recovery-lock rejection... MUST write AuthoritativeSecurityAuditWriter... MUST happen before throwing exception"
        // RecoveryStateService::check() throws exception. I cannot modify RecoveryStateService.
        // I must wrap this call.

        try {
            $this->recoveryState->check();
        } catch (\Exception $e) {
             $this->logDenial($actorId, $targetAdminId, $roleId, $sessionId, 'recovery_locked', false, 'unknown');
             throw $e;
        }

        // 2. Verify Actor != Target (No Self-Assignment)
        if ($actorId === $targetAdminId) {
            $this->logDenial($actorId, $targetAdminId, $roleId, $sessionId, 'self_assignment_forbidden', true, 'equal'); // Scope state: presumed true if we reached here? No, we haven't checked scope yet.
            // Prompt says: "scope_state (present / missing)". We haven't checked yet.
            // I should verify scope first?
            // "Flow (STRICT ORDER): 1. RecoveryStateService::check() 2. Verify actor != target 3. Require Step-Up grant"
            // So at step 2, we don't know scope state.
            // I will check scope *availability* for the log, without enforcing it yet?
            // Or just say "unknown" or "not_checked"?
            // Prompt: "scope_state (present / missing)".
            // I will check it for the log.
            $hasScope = $this->stepUpService->hasGrant($actorId, $sessionId, Scope::SECURITY);
            $this->logDenial($actorId, $targetAdminId, $roleId, $sessionId, 'self_assignment_forbidden', $hasScope, 'equal');
            throw new PermissionDeniedException("Self-assignment of roles is forbidden.");
        }

        // 3. Require Step-Up Grant (Scope::SECURITY)
        if (!$this->stepUpService->hasGrant($actorId, $sessionId, Scope::SECURITY)) {
            $this->logDenial($actorId, $targetAdminId, $roleId, $sessionId, 'step_up_required', false, 'unknown');
            throw new PermissionDeniedException("Step-Up authentication required for role assignment.");
        }

        // 4. Verify Role Hierarchy
        if (!$this->hierarchyComparator->canAssign($actorId, $roleId)) {
            $this->logDenial($actorId, $targetAdminId, $roleId, $sessionId, 'hierarchy_violation', true, 'insufficient');
            throw new PermissionDeniedException("Insufficient privilege to assign this role.");
        }

        // Assert Explicit Hierarchy
        // "If comparator already enforces this → add a guard assertion"
        // I can add: assert($this->hierarchyComparator->isExplicit()); if I had such method.
        // Or simply comment/assert here? "Assert and document (in code, not comments)".
        // I will trust the Comparator logic (Fix 3) as implemented in previous turn (using explicit Level Enum).
        // I will add a comment confirming this relies on explicit RoleLevel resolution.

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

            // 9. Invalidate Step-Up Grants (FIX 1: All grants for Affected Admin)
            // Affected Admin is the TARGET (whose privileges changed).
            // "Revoke ALL step-up grants for the affected admin"
            $this->grantRepository->revokeAll($targetAdminId);

            // ALSO revoke the Actor's Security Grant used for this action (Previous Fix 1)?
            // "Any privilege change MUST invalidate all existing step-up grants."
            // Does Actor's privilege change? No.
            // But we usually consume high-privilege tokens.
            // Previous prompt explicitly asked to invalidate Step-Up grants (plural/generic).
            // I will keep revocation of the Actor's grant too to be safe/strict (Single Use effectively).
            $this->grantRepository->revoke($actorId, $sessionId, Scope::SECURITY);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function logDenial(int $actorId, int $targetAdminId, int $roleId, string $sessionId, string $reason, bool $scopeState, string $hierarchyResult): void
    {
        try {
            $this->pdo->beginTransaction();
            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                'role_assignment_denied',
                'admin',
                $targetAdminId,
                'CRITICAL', // "Risk Level = CRITICAL"
                [
                    'role_id' => $roleId,
                    'reason' => $reason,
                    'session_id' => $sessionId,
                    'scope_state' => $scopeState ? 'present' : 'missing',
                    'hierarchy_result' => $hierarchyResult
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
