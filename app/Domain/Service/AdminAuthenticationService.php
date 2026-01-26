<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Admin\Enum\AdminStatusEnum;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Context\RequestContext;
use App\Domain\DTO\AdminLoginResultDTO;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\MustChangePasswordException;
use App\Infrastructure\Repository\AdminRepository;
use DateTimeImmutable;
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
