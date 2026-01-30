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

namespace App\Application\Auth;

use App\Application\Auth\DTO\AdminLoginUseCaseResultDTO;
use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\DTO\LoginRequestDTO;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\RememberMeService;
use Maatify\SharedCommon\Contracts\ClockInterface;
use DateTimeImmutable;

final readonly class AdminLoginService
{
    public function __construct(
        private AdminAuthenticationService $authService,
        private AdminSessionValidationRepositoryInterface $sessionRepository,
        private RememberMeService $rememberMeService,
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private ClockInterface $clock
    )
    {
    }

    public function login(
        LoginRequestDTO $dto,
        RequestContext $requestContext,
        bool $rememberMeRequested
    ): AdminLoginUseCaseResultDTO
    {
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($dto->email);

        $result = $this->authService->login($blindIndex, $dto->password, $requestContext);
        $token = $result->token;

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

        $expiresAt = new DateTimeImmutable($expiresAtStr, $this->clock->getTimezone());
        $now = $this->clock->now();
        $maxAge = $expiresAt->getTimestamp() - $now->getTimestamp();
        if ($maxAge < 0) {
            $maxAge = 0;
        }

        $rememberMeToken = null;
        if ($rememberMeRequested) {
            $rememberMeToken = $this->rememberMeService->issue($adminId, $requestContext);
        }

        return new AdminLoginUseCaseResultDTO(
            authToken             : $token,
            authTokenMaxAgeSeconds: $maxAge,
            rememberMeToken       : $rememberMeToken,
            adminId               : $adminId
        );
    }
}
