<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\ActivityLog\Action\AdminActivityAction;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Domain\DTO\LoginRequestDTO;
use App\Domain\DTO\LoginResponseDTO;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Service\AdminAuthenticationService;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\AuthLoginSchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class AuthController
{
    public function __construct(
        private AdminAuthenticationService $authService,
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private ValidationGuard $validationGuard,
        private AdminActivityLogService $adminActivityLogService,
    ) {}

    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $this->validationGuard->check(new AuthLoginSchema(), $data);

        $dto = new LoginRequestDTO(
            (string) $data['email'],
            (string) $data['password']
        );

        // Blind Index Calculation
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($dto->email);

        try {
            $result = $this->authService->login($blindIndex, $dto->password);

            // 🔹 Construct Contexts (Canonical)
            $adminContext = new AdminContext($result->adminId);

            $requestContext = $request->getAttribute(RequestContext::class);
            if (! $requestContext instanceof RequestContext) {
                // Should be caught by middleware hard-fail, but type safety check
                throw new \RuntimeException('Request Context not present');
            }

            $this->adminActivityLogService->log(
                adminContext: $adminContext,
                requestContext: $requestContext,
                action: AdminActivityAction::LOGIN_SUCCESS,
                metadata: [
                    'method' => 'password',
                ]
            );

            $responseDto = new LoginResponseDTO($result->token);
            $response->getBody()->write((string) json_encode($responseDto));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidCredentialsException $e) {
            // ❌ No admin context here (unknown identity)
            $response->getBody()->write((string) json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        } catch (AuthStateException $e) {
            $response->getBody()->write((string) json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
    }
}
