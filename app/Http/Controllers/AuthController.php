<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\AdminContext;
use App\Context\Resolver\RequestContextResolver;
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
        private string $blindIndexKey,
        private ValidationGuard $validationGuard,
        private RequestContextResolver $requestContextResolver,
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
        $blindIndex = hash_hmac('sha256', $dto->email, $this->blindIndexKey);
        assert(is_string($blindIndex));

        // 🔹 Build RequestContext ONCE
        $requestContext = $this->requestContextResolver->resolve($request);

        try {
            $result = $this->authService->login($blindIndex, $dto->password);
            $adminContext = AdminContext::fromAdminId($result->adminId);
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
