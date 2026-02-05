<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-29 09:58
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Auth;

use Maatify\AdminKernel\Application\Auth\DTO\ResendEmailVerificationRequestDTO;
use Maatify\AdminKernel\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use Maatify\AdminKernel\Application\Verification\VerificationNotificationDispatcherInterface;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminIdentifierLookupInterface;
use Maatify\AdminKernel\Domain\Contracts\VerificationCode\VerificationCodeGeneratorInterface;
use Maatify\AdminKernel\Domain\Enum\IdentityTypeEnum;
use Maatify\AdminKernel\Domain\Enum\VerificationPurposeEnum;
use Throwable;

final readonly class ResendEmailVerificationService
{
    public function __construct(
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private AdminIdentifierLookupInterface $lookupInterface,
        private VerificationCodeGeneratorInterface $generator,
        private VerificationNotificationDispatcherInterface $dispatcher,
    )
    {
    }

    public function resend(ResendEmailVerificationRequestDTO $request): void
    {
        if ($request->email === '') {
            return;
        }

        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($request->email);
        $identifierDTO = $this->lookupInterface->findByBlindIndex($blindIndex);

        if ($identifierDTO === null) {
            return;
        }

        try {
            $generated = $this->generator->generate(
                IdentityTypeEnum::Admin,
                (string)$identifierDTO->adminId,
                VerificationPurposeEnum::EmailVerification
            );

            $this->dispatcher->dispatch(
                identityType: IdentityTypeEnum::Admin,
                identityId  : (string)$identifierDTO->adminId,
                purpose     : VerificationPurposeEnum::EmailVerification,
                recipient   : $request->email,
                plainCode   : $generated->plainCode,
                context     : ['expires_in' => 600],
                language    : 'en'
            );
        } catch (Throwable) {
            // best-effort only
        }
    }
}
