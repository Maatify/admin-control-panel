<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Context\RequestContext;
use App\Domain\Contracts\AdminTotpSecretStoreInterface;
use App\Domain\Contracts\StepUpGrantRepositoryInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\DTO\StepUpGrant;
use App\Domain\DTO\TotpVerificationResultDTO;
use App\Domain\Enum\Scope;
use App\Domain\Enum\SessionState;
use Maatify\SharedCommon\Contracts\ClockInterface;
use DateTimeImmutable;
use PDO;

readonly class StepUpService
{
    public function __construct(
        private StepUpGrantRepositoryInterface $grantRepository,
        private AdminTotpSecretStoreInterface $totpSecretStore,
        private TotpServiceInterface $totpService,
        private RecoveryStateService $recoveryState,
        private PDO $pdo,
        private ClockInterface $clock
    ) {
    }

    public function verifyTotp(
        int $adminId,
        string $token,
        string $code,
        RequestContext $context,
        ?Scope $requestedScope = null
    ): TotpVerificationResultDTO {
        $this->recoveryState->enforce(RecoveryStateService::ACTION_OTP_VERIFY, $adminId, $context);

        $sessionId = hash('sha256', $token);

        $secret = $this->totpSecretStore->retrieve($adminId);
        if ($secret === null) {

            return new TotpVerificationResultDTO(false, 'TOTP not enrolled');
        }

        if (!$this->totpService->verify($secret, $code)) {

            return new TotpVerificationResultDTO(false, 'Invalid code');
        }

        $this->pdo->beginTransaction();
        try {
            if ($requestedScope !== null && $requestedScope !== Scope::LOGIN) {
                $this->issueScopedGrant($adminId, $token, $requestedScope, $context);
            } else {
                $this->issuePrimaryGrant($adminId, $token, $context);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return new TotpVerificationResultDTO(true);
    }

    public function enableTotp(
        int $adminId,
        string $token,
        string $secret,
        string $code,
        RequestContext $context
    ): bool {
        $this->recoveryState->enforce(RecoveryStateService::ACTION_OTP_VERIFY, $adminId, $context);

        $sessionId = hash('sha256', $token);

        if (!$this->totpService->verify($secret, $code)) {

            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $this->totpSecretStore->store($adminId, $secret);

            $this->issuePrimaryGrant($adminId, $token, $context);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return true;
    }

    public function issuePrimaryGrant(int $adminId, string $token, RequestContext $context): void
    {
        $sessionId = hash('sha256', $token);

        $grant = new StepUpGrant(
            $adminId,
            $sessionId,
            Scope::LOGIN,
            $this->getRiskHash($context),
            $this->clock->now(),
            $this->clock->now()->modify('+2 hours'),
            false
        );

        $this->grantRepository->save($grant);
    }

    public function issueScopedGrant(int $adminId, string $token, Scope $scope, RequestContext $context): void
    {
        $sessionId = hash('sha256', $token);

        $grant = new StepUpGrant(
            $adminId,
            $sessionId,
            $scope,
            $this->getRiskHash($context),
            $this->clock->now(),
            $this->getScopeTtl($scope),
            false
        );

        $this->grantRepository->save($grant);
    }

    public function logDenial(int $adminId, string $token, Scope $requiredScope, RequestContext $context): void
    {
        $sessionId = hash('sha256', $token);
    }

    public function hasGrant(int $adminId, string $token, Scope $scope, RequestContext $context): bool
    {
        $sessionId = hash('sha256', $token);

        $grant = $this->grantRepository->find($adminId, $sessionId, $scope);
        if ($grant === null) {
            return false;
        }

        if (!hash_equals($grant->riskContextHash, $this->getRiskHash($context))) {

            $this->pdo->beginTransaction();
            try {
                $this->grantRepository->revoke($adminId, $sessionId, $scope);

                $this->pdo->commit();
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
            }

            return false;
        }

        if ($grant->expiresAt < $this->clock->now()) {
            return false;
        }

        if ($grant->singleUse) {
            $this->pdo->beginTransaction();
            try {
                $this->grantRepository->revoke($adminId, $sessionId, $scope);

                $this->pdo->commit();
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
                return false;
            }
        }

        return true;
    }

    public function getSessionState(int $adminId, string $token, RequestContext $context): SessionState
    {
        $sessionId = hash('sha256', $token);

        $primaryGrant = $this->grantRepository->find($adminId, $sessionId, Scope::LOGIN);

        if ($primaryGrant !== null && $primaryGrant->expiresAt > $this->clock->now()) {
            if (hash_equals($primaryGrant->riskContextHash, $this->getRiskHash($context))) {
                return SessionState::ACTIVE;
            }
        }

        return SessionState::PENDING_STEP_UP;
    }

    private function getRiskHash(RequestContext $context): string
    {
        return hash('sha256', $context->ipAddress . '|' . $context->userAgent);
    }

    private function getScopeTtl(Scope $scope): DateTimeImmutable
    {
        return match ($scope) {
            Scope::ADMIN_CREATE => $this->clock->now()->modify('+5 minutes'),
            default => $this->clock->now()->modify('+15 minutes'),
        };
    }
}
