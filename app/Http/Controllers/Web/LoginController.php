<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\DTO\LoginRequestDTO;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Service\AdminAuthenticationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class LoginController
{
    public function __construct(
        private AdminAuthenticationService $authService,
        private string $blindIndexKey,
        private Twig $view
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (!is_array($data) || !isset($data['email']) || !isset($data['password'])) {
             return $this->view->render($response, 'login.twig', ['error' => 'Invalid request']);
        }

        $dto = new LoginRequestDTO((string)$data['email'], (string)$data['password']);

        // Blind Index Calculation
        $blindIndex = hash_hmac('sha256', $dto->email, $this->blindIndexKey);
        assert(is_string($blindIndex));

        try {
            // We get the token.
            $token = $this->authService->login($blindIndex, $dto->password);

            $response = $response->withHeader('Set-Cookie', "auth_token=$token; Path=/; HttpOnly; SameSite=Strict");

            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        } catch (InvalidCredentialsException | AuthStateException $e) {
            // Requirement: Generic login error message
            return $this->view->render($response, 'login.twig', ['error' => 'Authentication failed.']);
        }
    }
}
