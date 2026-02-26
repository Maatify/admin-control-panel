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

        // 1. Validate Input
        $this->validationGuard->check(new RedirectTokenSignSchema(), $data);

        $dto = new RedirectTokenRequestDTO((string)($data['path'] ?? ''));

        // 2. Generate Token
        // NOTE: RedirectTokenService enforces strict path validation internally.
        // It will reject invalid paths by returning a useless token or throwing (if we changed it),
        // but current implementation allows create() to return a token for any path, verify() validates it.
        // Wait, create() implementation is "dumb", it just signs.
        // verify() is where the strict checks are.
        // So we are signing potentially bad input here?
        // The contract for create() in the service accepts string.
        // The client (JS) will use this token to redirect to /login?r=... or /2fa/verify?r=...
        // The *target* controller (Login/TwoFactor) will call verify().
        // If the path is invalid (e.g. external), verify() returns null, and they fallback to dashboard.
        // This is safe.

        $token = $this->tokenService->create($dto->path);

        // 3. Respond
        $result = new RedirectTokenResponseDTO(
            token: $token,
            // Convenience URL for frontend
            redirect_url: '/2fa/verify?r=' . $token
        );

        $response->getBody()->write((string)json_encode($result, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
