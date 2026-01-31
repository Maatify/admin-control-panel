<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm)
 * @since       2026-01-19
 * @note        Two-Factor Authentication (TOTP) Enrollment Service
 *              Handles setup + enable flow using server-side session storage.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Service;

use Maatify\AdminKernel\Application\Crypto\TotpSecretCryptoServiceInterface;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\AdminSessionRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminTotpSecretRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\TotpServiceInterface;
use Maatify\AdminKernel\Domain\DTO\Crypto\EncryptedPayloadDTO;
use Maatify\AdminKernel\Domain\DTO\TotpEnrollmentConfig;
use Maatify\AdminKernel\Domain\DTO\TwoFactorEnrollmentViewDTO;
use Maatify\AdminKernel\Domain\Exception\TwoFactorAlreadyEnrolledException;
use Maatify\AdminKernel\Domain\Exception\TwoFactorEnrollmentFailedException;
use Maatify\AdminKernel\Domain\Support\CorrelationId;
use PDO;

/**
 * Two-Factor Authentication (TOTP) Enrollment Service
 *
 *  Handles setup + enable flow using server-side session storage.
 *
 *  - Returns provisioning URI (otpauth://) + plain secret
 *  - Plain secret is required for manual entry in authenticator apps
 */
final class TwoFactorEnrollmentService
{
    public function __construct(
        private readonly AdminTotpSecretRepositoryInterface $totpSecretRepository,
        private readonly AdminSessionRepositoryInterface $sessionRepository,
        private readonly TotpServiceInterface $totpService,
        private readonly TotpSecretCryptoServiceInterface $crypto,
        private readonly TotpEnrollmentConfig $totpEnrollmentConfig,
        private readonly StepUpService $stepUpService,
        private readonly PDO $pdo,
    )
    {
    }

    /**
     * STEP 1: Prepare TOTP enrollment
     *
     * - Generate new TOTP secret
     * - Store raw secret temporarily in server-side session
     * - Return provisioning URI + plain secret for UI display / manual entry
     */
    public function prepareEnrollment(
        int $adminId,
        string $sessionHash,
        RequestContext $context
    ): TwoFactorEnrollmentViewDTO
    {
        // Fail-closed: already enrolled
        if ($this->totpSecretRepository->get($adminId) !== null) {
            throw new TwoFactorAlreadyEnrolledException(
                sprintf('TOTP already enrolled (request_id=%s)', $context->requestId)
            );
        }

        // 🔑 STEP 0: Reuse existing pending enrollment if present and valid
        $pending = $this->sessionRepository->getPendingTotpEnrollmentByHash($sessionHash);

        if ($pending !== null) {
            $issuedAt = new \DateTimeImmutable($pending['issued_at']);

            if (
                $issuedAt->modify('+' . $this->totpEnrollmentConfig->totpEnrollmentTtlSeconds . ' seconds')
                >= new \DateTimeImmutable()
            ) {
                // Pending still valid → reuse same secret
                $encryptedPayload = new EncryptedPayloadDTO(
                    ciphertext: $pending['seed_ciphertext'],
                    iv        : $pending['seed_iv'],
                    tag       : $pending['seed_tag'],
                    keyId     : $pending['seed_key_id']
                );

                $secret = $this->crypto->decryptTotpSeed($encryptedPayload);

                $qrUri = $this->totpService->generateProvisioningUri(
                    issuer     : $this->totpEnrollmentConfig->totpIssuer,
                    accountName: 'admin:' . $adminId,
                    secret     : $secret
                );

                return new TwoFactorEnrollmentViewDTO(
                    qrUri : $qrUri,
                    secret: $secret
                );
            }

            // Pending expired → clear it before generating new
            $this->sessionRepository->clearPendingTotpEnrollmentByHash($sessionHash);
        }

        // 🔑 STEP 1: Generate new TOTP secret (only if no valid pending exists)
        $secret = $this->totpService->generateSecret();

        // Store pending secret (encrypted, session-bound)
        $encrypted = $this->crypto->encryptTotpSeed($secret);

        $this->sessionRepository->storePendingTotpEnrollmentByHash(
            $sessionHash,
            $encrypted->ciphertext,
            $encrypted->iv,
            $encrypted->tag,
            $encrypted->keyId,
            new \DateTimeImmutable()
        );

        // Generate provisioning URI (otpauth://)
        $qrUri = $this->totpService->generateProvisioningUri(
            issuer     : $this->totpEnrollmentConfig->totpIssuer,
            accountName: 'admin:' . $adminId,
            secret     : $secret
        );

        return new TwoFactorEnrollmentViewDTO(
            qrUri : $qrUri,
            secret: $secret
        );
    }


    /**
     * STEP 2: Enable TOTP enrollment
     *
     * - Verify OTP against pending secret
     * - Encrypt secret using TOTP crypto service
     * - Persist encrypted secret
     * - Write authoritative audit
     * - Clear pending session state
     */
    public function enableEnrollment(
        int $adminId,
        string $token,
        string $sessionHash,
        string $otpCode,
        RequestContext $context
    ): bool
    {
        $pending = $this->sessionRepository->getPendingTotpEnrollmentByHash($sessionHash);

        if ($pending === null) {
            throw new TwoFactorEnrollmentFailedException('No pending TOTP enrollment found');
        }

        $issuedAt = new \DateTimeImmutable($pending['issued_at']);
        if (
            $issuedAt->modify('+' . $this->totpEnrollmentConfig->totpEnrollmentTtlSeconds . ' seconds')
            < new \DateTimeImmutable()
        ) {
            $this->sessionRepository->clearPendingTotpEnrollmentByHash($sessionHash);
            throw new TwoFactorEnrollmentFailedException('TOTP enrollment expired');
        }

        $encryptedPayload = new EncryptedPayloadDTO(
            ciphertext: $pending['seed_ciphertext'],
            iv        : $pending['seed_iv'],
            tag       : $pending['seed_tag'],
            keyId     : $pending['seed_key_id']
        );

        $secret = $this->crypto->decryptTotpSeed($encryptedPayload);

        // Verify OTP code (UI-level failure, retry allowed)
        if (! $this->totpService->verify($secret, $otpCode)) {

            // Do NOT clear pending enrollment — user may retry
            return false;
        }

        $correlationId = CorrelationId::generate();

        $this->pdo->beginTransaction();
        try {
            // Encrypt TOTP secret (context-bound)
            $encrypted = $this->crypto->encryptTotpSeed($secret);

            // Persist encrypted secret
            $this->totpSecretRepository->save($adminId, $encrypted);

            // ✅ Promote current session to ACTIVE by issuing primary Step-Up grant
            // This prevents redirect loop to /2fa/verify after successful enrollment.
            $this->stepUpService->issuePrimaryGrant($adminId, $token, $context);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        // Clear pending enrollment ONLY after successful completion
        $this->sessionRepository->clearPendingTotpEnrollmentByHash($sessionHash);

        return true;
    }


}
