<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-29 09:46
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Auth\DTO\ChangePasswordRequestDTO;
use App\Application\Auth\DTO\ChangePasswordResultDTO;
use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Service\PasswordService;
use App\Domain\Service\RecoveryStateService;
use PDO;
use Throwable;

final readonly class ChangePasswordService
{
    public function __construct(
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private AdminIdentifierLookupInterface $identifierLookup,
        private AdminPasswordRepositoryInterface $passwordRepository,
        private PasswordService $passwordService,
        private RecoveryStateService $recoveryState,
        private PDO $pdo,
    )
    {
    }

    public function change(ChangePasswordRequestDTO $request): ChangePasswordResultDTO
    {
        $context = $request->requestContext;

        // 🔒 Recovery enforcement
        $this->recoveryState->enforce(
            RecoveryStateService::ACTION_PASSWORD_CHANGE,
            null,
            $context
        );

        // 1️⃣ Resolve Admin ID
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($request->email);
        $identifierDTO = $this->identifierLookup->findByBlindIndex($blindIndex);

        if ($identifierDTO === null) {
            return new ChangePasswordResultDTO(false);
        }

        $adminId = $identifierDTO->adminId;

        // 2️⃣ Verify current password
        $record = $this->passwordRepository->getPasswordRecord($adminId);
        if (
            $record === null
            || ! $this->passwordService->verify(
                $request->currentPassword,
                $record->hash,
                $record->pepperId
            )
        ) {
            return new ChangePasswordResultDTO(false);
        }

        // 3️⃣ Persist password change (transactional)
        $this->pdo->beginTransaction();
        try {
            $hashResult = $this->passwordService->hash($request->newPassword);

            $this->passwordRepository->savePassword(
                $adminId,
                $hashResult['hash'],
                $hashResult['pepper_id'],
                false // clear must_change_password
            );

            // 🧾 Authoritative Audit

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return new ChangePasswordResultDTO(true);
    }
}
