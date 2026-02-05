<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-29 08:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Auth;

use DateTimeImmutable;
use Maatify\AdminKernel\Application\Auth\DTO\AdminLoginUseCaseResultDTO;
use Maatify\AdminKernel\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\Abuse\AbuseCookieServiceInterface;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminSessionValidationRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\LoginRequestDTO;
use Maatify\AdminKernel\Domain\Exception\InvalidCredentialsException;
use Maatify\AdminKernel\Domain\Service\AdminAuthenticationService;
use Maatify\AdminKernel\Domain\Service\RememberMeService;
use Maatify\SharedCommon\Contracts\ClockInterface;

final readonly class AdminLoginService
{
    public function __construct(
        private AdminAuthenticationService $authService,
        private AdminSessionValidationRepositoryInterface $sessionRepository,
        private RememberMeService $rememberMeService,
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private ClockInterface $clock,

        // 🔒 Abuse Protection (Cookie issuance only – no HTTP here)
        private AbuseCookieServiceInterface $abuseCookieService
    ) {
    }

    public function login(
        LoginRequestDTO $dto,
        RequestContext $requestContext,
        bool $rememberMeRequested,
        ?string $existingDeviceId
    ): AdminLoginUseCaseResultDTO {
        // 1. Blind Index (lookup)
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($dto->email);

        // 2. Domain Authentication
        $result = $this->authService->login($blindIndex, $dto->password, $requestContext);
        $token  = $result->token;

        // 3. Session Validation
        $session = $this->sessionRepository->findSession($token);
        if ($session === null) {
            throw new InvalidCredentialsException('Session creation failed.');
        }

        $expiresAtStr = $session['expires_at'];
        if (! is_string($expiresAtStr) || $expiresAtStr === '') {
            throw new InvalidCredentialsException('Session expiry not available.');
        }

        $adminId = $session['admin_id'];
        if (! is_int($adminId)) {
            // fallback: if repo returns numeric string
            if (is_string($adminId) && ctype_digit($adminId)) {
                $adminId = (int)$adminId;
            } else {
                throw new InvalidCredentialsException('Session admin_id not available.');
            }
        }

        // 4. TTL Calculation
        $expiresAt = new DateTimeImmutable($expiresAtStr, $this->clock->getTimezone());
        $now       = $this->clock->now();

        $maxAge = $expiresAt->getTimestamp() - $now->getTimestamp();
        if ($maxAge < 0) {
            $maxAge = 0;
        }

        // 5. Remember-Me (optional)
        $rememberMeToken = null;
        if ($rememberMeRequested) {
            $rememberMeToken = $this->rememberMeService->issue($adminId, $requestContext);
        }

        // 6. 🔐 Abuse Cookie Issue (contract-compliant, no device identity available)
        $abuseCookie = $this->abuseCookieService->issue(
            sessionToken     : $token,
            context          : $requestContext,
            existingDeviceId : $existingDeviceId
        );

        // 7. Use-Case Result (DTO only – no headers)
        return new AdminLoginUseCaseResultDTO(
            authToken             : $token,
            authTokenMaxAgeSeconds: $maxAge,
            rememberMeToken       : $rememberMeToken,
            adminId               : $adminId,
            abuseCookie           : $abuseCookie
        );
    }
}
