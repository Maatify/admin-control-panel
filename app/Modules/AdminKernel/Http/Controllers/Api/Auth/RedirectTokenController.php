<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Auth;

use Maatify\AdminKernel\Domain\DTO\RedirectTokenRequestDTO;
use Maatify\AdminKernel\Domain\DTO\RedirectTokenResponseDTO;
use Maatify\AdminKernel\Domain\Security\RedirectToken\RedirectTokenServiceInterface;
use Maatify\AdminKernel\Validation\Schemas\Auth\RedirectTokenSignSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class RedirectTokenController
{
    public function __construct(
        private RedirectTokenServiceInterface $tokenService,
        private ValidationGuard $validationGuard
    ) {
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array)$request->getParsedBody();

        $this->validationGuard->check(new RedirectTokenSignSchema(), $data);

        $dto = new RedirectTokenRequestDTO((string)($data['path'] ?? ''));

        // The RedirectTokenService handles structural validity.
        // The token is cryptographically signed here.
        // It will be verified (path constraints, expiry, etc.) by the consuming controller (Login/TwoFactor).
        $token = $this->tokenService->create($dto->path);

        $result = new RedirectTokenResponseDTO(
            token: $token,
            redirect_url: '/2fa/verify?r=' . $token
        );

        $response->getBody()->write((string)json_encode($result, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
