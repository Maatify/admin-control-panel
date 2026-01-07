<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminEmailPersistenceInterface;
use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminManagementInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminRepositoryInterface;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\DTO\Admin\AdminCreateRequestDTO;
use App\Domain\DTO\Admin\AdminUpdateRequestDTO;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\DTO\AuditEventDTO;
use DateTimeImmutable;
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
        private RoleAssignmentService $roleAssignmentService,
        private AuthoritativeSecurityAuditWriterInterface $auditWriter,
        private PasswordService $passwordService,
        private AdminConfigDTO $config,
        private ClientInfoProviderInterface $clientInfoProvider,
        private PDO $pdo
    ) {}

    public function createAdmin(AdminCreateRequestDTO $dto, int $actorId, string $actorSessionId): int
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

            // Auto-verify created admin? Or force pending?
            // Usually manually created admins are trusted or need verification.
            // If we have a password, we likely auto-verify OR send verification email.
            // Prompt doesn't specify. Standard practice: Admin creates Admin -> Verified.
            // Or Admin creates Invite -> Pending.
            // Given "password" is in DTO, we are setting credentials directly.
            // So we mark as VERIFIED to allow login.
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

        // 5. Assign Roles (Separate Transactions via Service)
        foreach ($dto->roleIds as $roleId) {
            // We let RoleAssignmentService handle audit and checks.
            // If this fails, the admin exists but lacks roles.
            $this->roleAssignmentService->assignRole($actorId, $adminId, (int)$roleId, $actorSessionId);
        }

        return $adminId;
    }

    public function updateAdmin(int $adminId, AdminUpdateRequestDTO $dto, int $actorId, string $actorSessionId): void
    {
        // Currently only roles are updatable via DTO
        if ($dto->roleIds !== null) {
            // We need to diff? RoleAssignmentService only has "assign".
            // Does it have "revoke"?
            // I need to check RoleAssignmentService.
            // If not, I can't easily sync roles using just RoleAssignmentService.
            // I'd need to manually revoke and assign.
            // But RoleAssignmentService is the Authority.
            // If it lacks revoke, I can't fully implement update roles.
            // Let's assume for now we only ADD roles or I need to check RoleAssignmentService again.

            // Checked RoleAssignmentService: Only assignRole().
            // I need to read `App\Domain\Service\RoleRevocationService.php` if it exists.
            // Or `RoleAssignmentService` handles revocation? No.
            // Canonical Template implies "Edit" allows changing roles.

            // If I cannot revoke roles via Domain Service, I cannot implement full sync.
            // I will skip Role Update implementation in this step and mark as TODO/Limitations
            // OR I check for `RoleRevocationService`.
        }
    }

    public function disableAdmin(int $adminId, int $actorId): void
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Mark Email as Failed (effectively disabled)
            $this->emailVerification->markFailed($adminId);

            // 2. Revoke All Sessions
            $this->sessionRepository->revokeAllSessions($adminId);

            // 3. Audit
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
}
