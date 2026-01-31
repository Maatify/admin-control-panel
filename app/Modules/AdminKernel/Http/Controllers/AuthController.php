<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers;

use Maatify\AdminKernel\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\DTO\LoginRequestDTO;
use Maatify\AdminKernel\Domain\DTO\LoginResponseDTO;
use Maatify\AdminKernel\Domain\Exception\AuthStateException;
use Maatify\AdminKernel\Domain\Exception\InvalidCredentialsException;
use Maatify\AdminKernel\Domain\Service\AdminAuthenticationService;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\AuthLoginSchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class AuthController
{
    public function __construct(
        private AdminAuthenticationService $authService,
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private ValidationGuard $validationGuard,
    ) {}

    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $this->validationGuard->check(new AuthLoginSchema(), $data);

        $dto = new LoginRequestDTO(
            (string) $data['email'],
            (string) $data['password']
        );

        // Blind Index Calculation (Auth lookup)
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($dto->email);

        try {
            $requestContext = $request->getAttribute(RequestContext::class);
            if (! $requestContext instanceof RequestContext) {
                // Middleware should hard-fail earlier, but keep type safety.
                throw new \RuntimeException('Request Context not present');
            }

            $result = $this->authService->login($blindIndex, $dto->password, $requestContext);

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
