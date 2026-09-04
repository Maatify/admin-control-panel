<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Service;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Admin\Enum\AdminStatusEnum;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminIdentifierLookupInterface;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminPasswordRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminSessionRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\AdminLoginResultDTO;
use Maatify\AdminKernel\Domain\Enum\VerificationStatus;
use Maatify\AdminKernel\Domain\Exception\AuthStateException;
use Maatify\AdminKernel\Domain\Exception\InvalidCredentialsException;
use Maatify\AdminKernel\Domain\Exception\MustChangePasswordException;
use Maatify\AdminKernel\Infrastructure\Repository\AdminRepository;
use PDO;

readonly class AdminAuthenticationService
{
    public function __construct(
        private AdminIdentifierLookupInterface $lookupRepository,
        private AdminPasswordRepositoryInterface $passwordRepository,
        private AdminSessionRepositoryInterface $sessionRepository,
        private RecoveryStateService $recoveryState,
        private PDO $pdo,
        private PasswordService $passwordService,
        private AdminRepository $adminRepository,
    ) {
    }

    public function login(string $blindIndex, string $password, RequestContext $context): AdminLoginResultDTO
    {
        $this->recoveryState->enforce(RecoveryStateService::ACTION_LOGIN, null, $context);

        // 1. Look up Admin ID by Blind Index
        // AdminEmailIdentifierDTO
        $adminEmailIdentifierDTO = $this->lookupRepository->findByBlindIndex($blindIndex);
        if ($adminEmailIdentifierDTO === null) {
            throw new InvalidCredentialsException("Invalid credentials.");
        }

        $adminId = $adminEmailIdentifierDTO->adminId;
        // 2. Verify Password
        $record = $this->passwordRepository->getPasswordRecord($adminId);
        if ($record === null || !$this->passwordService->verify($password, $record->hash, $record->pepperId)) {
            throw new InvalidCredentialsException("Invalid credentials.");
        }

        // 🔒 3 Enforce Admin Lifecycle Status
        $status = $this->adminRepository->getStatus($adminId);
        if (! $status->canAuthenticate()) {

            throw new AuthStateException(
                match ($status) {
                    AdminStatusEnum::SUSPENDED =>
                    AuthStateException::REASON_SUSPENDED,

                    AdminStatusEnum::DISABLED =>
                    AuthStateException::REASON_DISABLED,

                    default =>
                    AuthStateException::REASON_DISABLED, // fail-closed
                },
                match ($status) {
                    AdminStatusEnum::SUSPENDED =>
                    'Your account is temporarily suspended. Please contact the system administrator.',

                    AdminStatusEnum::DISABLED =>
                    'Your account has been disabled and is no longer active.',

                    default =>
                    'Authentication failed.',
                }
            );

        }

        // 4. Check Verification Status
        if ($adminEmailIdentifierDTO->verificationStatus !== VerificationStatus::VERIFIED) {
            throw new AuthStateException(
                AuthStateException::REASON_NOT_VERIFIED,
                'Identifier is not verified.'
            );
        }

        // 🔒 5. Enforce Must-Change-Password
        if ($record->mustChangePassword) {
            throw new MustChangePasswordException('Password change required.');
        }

        // 6. Transactional Login (Upgrade + Session)
        $this->pdo->beginTransaction();
        try {
            // 6.1 Upgrade-on-Login
            if ($this->passwordService->needsRehash($record->hash, $record->pepperId)) {
                $newHash = $this->passwordService->hash($password);
                $this->passwordRepository->savePassword(
                    $adminId,
                    $newHash['hash'],
                    $newHash['pepper_id'],
                    false // 🔒 explicit: this is NOT a temporary password
                );
            }

            // 6.2 Create Session
            $token = $this->sessionRepository->createSession($adminId);
            $sessionId = hash('sha256', $token);

            // ✅ Session Identity Snapshot (UI-only)
            $identity = $this->adminRepository->getIdentitySnapshot($adminId);
            $this->sessionRepository->storeSessionIdentityByHash($sessionId, $identity);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return new AdminLoginResultDTO(
            adminId: $adminId,
            token: $token
        );
    }

    public function logoutSession(int $adminId, string $token, RequestContext $context): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->sessionRepository->revokeSession($token);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
