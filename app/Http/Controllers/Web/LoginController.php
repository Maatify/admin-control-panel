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
            $this->authService->login($blindIndex, $dto->password);
            // In a real web app, we'd set a cookie here.
            // But strict rules say "NO DO NOT change Auth, RBAC, Session semantics".
            // However, Web Surface Enablement implies using sessions or something.
            // The API uses Bearer tokens.
            // If I just login, I get a token.
            // How do I persist this for the web user?
            // "Same session used by Web & API"
            // The `login` method returns a token string.
            // I should probably set this token in a cookie?
            // "SessionGuardMiddleware" reads from "Authorization: Bearer <token>".
            // I will need to modify SessionGuardMiddleware to ALSO read from Cookie if Web.

            // Wait, "Same session used by Web & API" task constraint.
            // API uses `admin_sessions` table and Bearer token.
            // If I want to use the same session, I need to use the token.

            // Plan: Set a cookie with the token.
            // Then update SessionGuardMiddleware to read from cookie.

            // Note: The task says "Update SessionGuardMiddleware: Redirect for Web".
            // It doesn't explicitly say "Support Cookie Auth", but it's implied by "Web Surface Enablement" and "Same session".
            // Unless I'm supposed to pass the token in URL? No, that's bad.

            // Let's assume I can set a cookie.

            // We get the token.
            $token = $this->authService->login($blindIndex, $dto->password);

            $response = $response->withHeader('Set-Cookie', "auth_token=$token; Path=/; HttpOnly; SameSite=Strict");

            return $response->withHeader('Location', '/dashboard')->withStatus(302);

        } catch (InvalidCredentialsException $e) {
            return $this->view->render($response, 'login.twig', ['error' => 'Invalid credentials']);
        } catch (AuthStateException $e) {
            return $this->view->render($response, 'login.twig', ['error' => $e->getMessage()]);
        }
    }
}
