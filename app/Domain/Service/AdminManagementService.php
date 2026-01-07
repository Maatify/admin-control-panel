<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminEmailPersistenceInterface;
use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminManagementInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminRepositoryInterface;
use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\DTO\Admin\AdminCreateRequestDTO;
use App\Domain\DTO\Admin\AdminUpdateRequestDTO;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\Enum\Scope;
use App\Domain\Exception\PermissionDeniedException;
use DateTimeImmutable;
use LogicException;
use PDO;
use RuntimeException;

class AdminManagementService implements AdminManagementInterface
{
    public function __construct(
        private AdminRepositoryInterface $adminRepository,
        private AdminEmailPersistenceInterface $emailPersistence,
        private AdminEmailVerificationRepositoryInterface $emailVerification,
        private AdminPasswordRepositoryInterface $passwordRepository,
        private AdminSessionValidationRepositoryInterface $sessionRepository,
        private AdminRoleRepositoryInterface $roleRepository,
        private StepUpService $stepUpService,
        private StepUpGrantRepositoryInterface $grantRepository,
        private RoleHierarchyComparator $hierarchyComparator,
        private RecoveryStateService $recoveryState,
        private AuthoritativeSecurityAuditWriterInterface $auditWriter,
        private PasswordService $passwordService,
        private AdminConfigDTO $config,
        private ClientInfoProviderInterface $clientInfoProvider,
        private PDO $pdo
    ) {}

    public function createAdmin(AdminCreateRequestDTO $dto, int $actorId, string $actorToken): int
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Create Base Admin
            $adminId = $this->adminRepository->create();

            // 2. Email (Blind Index + Encryption)
            $blindIndexKey = $this->config->emailBlindIndexKey;
            if (strlen($blindIndexKey) < 32) {
                throw new RuntimeException("EMAIL_BLIND_INDEX_KEY missing or weak");
            }
            $blindIndex = hash_hmac('sha256', $dto->email, $blindIndexKey);

            $encryptedEmail = $this->encryptEmail($dto->email);

            $this->emailPersistence->addEmail($adminId, $blindIndex, $encryptedEmail);

            // Auto-verify created admin
            $this->emailVerification->markVerified($adminId, (new DateTimeImmutable())->format('Y-m-d H:i:s'));

            // 3. Password
            $hash = $this->passwordService->hash($dto->password);
            $this->passwordRepository->savePassword($adminId, $hash);

            // 4. Audit
            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                'admin_created',
                'admin',
                $adminId,
                'CRITICAL',
                [
                    'email_blind_index' => $blindIndex,
                    'roles_count' => count($dto->roleIds)
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        // 5. Assign Roles (Separate Transactions via internal logic)
        foreach ($dto->roleIds as $roleId) {
            $this->assignRoleInternal($actorId, $adminId, (int)$roleId, $actorToken);
        }

        return $adminId;
    }

    public function updateAdmin(int $adminId, AdminUpdateRequestDTO $dto, int $actorId, string $actorToken): void
    {
        if ($dto->roleIds !== null) {
            $currentRoles = $this->roleRepository->getRoleIds($adminId);
            $newRoles = $dto->roleIds;

            $toAdd = array_diff($newRoles, $currentRoles);
            $toRemove = array_diff($currentRoles, $newRoles);

            foreach ($toAdd as $roleId) {
                $this->assignRoleInternal($actorId, $adminId, (int)$roleId, $actorToken);
            }

            foreach ($toRemove as $roleId) {
                $this->revokeRoleInternal($actorId, $adminId, (int)$roleId, $actorToken);
            }
        }
    }

    private function assignRoleInternal(int $actorId, int $targetAdminId, int $roleId, string $actorToken): void
    {
        // 1. Recovery State Check
        $sessionId = hash('sha256', $actorToken);
        try {
            $this->recoveryState->enforce(RecoveryStateService::ACTION_ROLE_ASSIGNMENT, $actorId);
        } catch (\Exception $e) {
             $hasScope = $this->stepUpService->hasGrant($actorId, $actorToken, Scope::SECURITY);
             $this->logDenial($actorId, $targetAdminId, $roleId, $actorToken, 'recovery_locked', $hasScope, 'unknown');
             throw $e;
        }

        // 2. Verify Actor != Target (No Self-Assignment)
        if ($actorId === $targetAdminId) {
            $hasScope = $this->stepUpService->hasGrant($actorId, $actorToken, Scope::SECURITY);
            $this->logDenial($actorId, $targetAdminId, $roleId, $actorToken, 'self_assignment_forbidden', $hasScope, 'equal');
            throw new PermissionDeniedException("Self-assignment of roles is forbidden.");
        }

        // 3. Require Step-Up Grant (Scope::SECURITY)
        // Pass RAW TOKEN to hasGrant
        if (!$this->stepUpService->hasGrant($actorId, $actorToken, Scope::SECURITY)) {
            $this->logDenial($actorId, $targetAdminId, $roleId, $actorToken, 'step_up_required', false, 'unknown');
            throw new PermissionDeniedException("Step-Up authentication required for role assignment.");
        }

        // 4. Verify Role Hierarchy
        try {
            $this->hierarchyComparator->guardInvariants($actorId, $roleId);
        } catch (LogicException $e) {
             $this->logDenial($actorId, $targetAdminId, $roleId, $actorToken, 'hierarchy_ambiguous', true, 'ambiguous');
             throw new PermissionDeniedException("Role hierarchy is ambiguous. Assignment denied.");
        }

        if (!$this->hierarchyComparator->canAssign($actorId, $roleId)) {
            $this->logDenial($actorId, $targetAdminId, $roleId, $actorToken, 'hierarchy_violation', true, 'insufficient');
            throw new PermissionDeniedException("Insufficient privilege to assign this role.");
        }

        $riskHash = $this->getRiskHash();

        $this->pdo->beginTransaction();
        try {
            // 6. Persist Assignment
            $this->roleRepository->assign($targetAdminId, $roleId);

            // 7. Authoritative Audit (Success)
            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                'role_assigned',
                'admin',
                $targetAdminId,
                'CRITICAL',
                [
                    'role_id' => $roleId,
                    'session_id' => $sessionId, // HASHED
                    'risk_context_hash' => $riskHash
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            // 9. Invalidate Step-Up Grants
            $this->grantRepository->revokeAll($targetAdminId);
            $this->grantRepository->revoke($actorId, $sessionId, Scope::SECURITY);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function revokeRoleInternal(int $actorId, int $targetAdminId, int $roleId, string $actorToken): void
    {
        // Require Step-Up
        if (!$this->stepUpService->hasGrant($actorId, $actorToken, Scope::SECURITY)) {
            throw new PermissionDeniedException("Step-Up authentication required for role revocation.");
        }

        // Hierarchy check
        if (!$this->hierarchyComparator->canAssign($actorId, $roleId)) {
             throw new PermissionDeniedException("Insufficient privilege to revoke this role.");
        }

        $sessionId = hash('sha256', $actorToken);

        $this->pdo->beginTransaction();
        try {
            $this->roleRepository->revoke($targetAdminId, $roleId);

            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                'role_revoked',
                'admin',
                $targetAdminId,
                'CRITICAL',
                [
                    'role_id' => $roleId,
                    'session_id' => $sessionId,
                    'reason' => 'admin_update'
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->grantRepository->revokeAll($targetAdminId);
            $this->grantRepository->revoke($actorId, $sessionId, Scope::SECURITY);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function logDenial(int $actorId, int $targetAdminId, int $roleId, string $actorToken, string $reason, bool $scopeState, string $hierarchyResult): void
    {
        $sessionId = hash('sha256', $actorToken);
        $startedTransaction = false;
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
                $startedTransaction = true;
            }

            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                'role_assignment_denied',
                'admin',
                $targetAdminId,
                'CRITICAL',
                [
                    'role_id' => $roleId,
                    'session_id' => $sessionId,
                    'reason' => $reason,
                    'scope_security' => $scopeState ? 'present' : 'missing',
                    'hierarchy_result' => $hierarchyResult
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            if ($startedTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($startedTransaction) {
                $this->pdo->rollBack();
            }
        }
    }

    public function disableAdmin(int $adminId, int $actorId): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->emailVerification->markFailed($adminId);
            $this->sessionRepository->revokeAllSessions($adminId);

            $this->auditWriter->write(new AuditEventDTO(
                $actorId,
                'admin_disabled',
                'admin',
                $adminId,
                'CRITICAL',
                [
                    'ip_address' => $this->clientInfoProvider->getIpAddress(),
                    'reason' => 'manual_disable'
                ],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function encryptEmail(string $email): string
    {
        $key = $this->config->emailEncryptionKey;
        if (strlen($key) < 32) {
             throw new RuntimeException("EMAIL_ENCRYPTION_KEY missing or weak.");
        }
        $iv = random_bytes(12);
        $tag = "";
        // Use OPENSSL_RAW_DATA for compatibility with AdminController
        $encrypted = openssl_encrypt($email, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $encrypted);
    }

    private function getRiskHash(): string
    {
        $ip = $this->clientInfoProvider->getIpAddress();
        $ua = $this->clientInfoProvider->getUserAgent();
        return hash('sha256', $ip . '|' . $ua);
    }
}
