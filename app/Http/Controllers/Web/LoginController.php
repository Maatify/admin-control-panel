<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\DTO\LoginRequestDTO;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Service\AdminAuthenticationService;
use App\Domain\Service\RememberMeService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use DateTimeImmutable;

readonly class LoginController
{
    public function __construct(
        private AdminAuthenticationService $authService,
        private AdminSessionValidationRepositoryInterface $sessionRepository,
        private RememberMeService $rememberMeService,
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
        $rememberMe = isset($data['remember_me']) && $data['remember_me'] === 'on';

        // Blind Index Calculation
        $blindIndex = hash_hmac('sha256', $dto->email, $this->blindIndexKey);
        assert(is_string($blindIndex));

        try {
            // We get the token.
            $token = $this->authService->login($blindIndex, $dto->password);

            // Fetch session details to align cookie expiration
            $session = $this->sessionRepository->findSession($token);
            if ($session === null) {
                throw new InvalidCredentialsException("Session creation failed.");
            }
            $adminId = $session['admin_id'];

            $expiresAt = new DateTimeImmutable($session['expires_at']);
            $now = new DateTimeImmutable();
            $maxAge = $expiresAt->getTimestamp() - $now->getTimestamp();

            if ($maxAge < 0) {
                $maxAge = 0;
            }

            $isSecure = $request->getUri()->getScheme() === 'https';
            $secureFlag = $isSecure ? 'Secure;' : '';

            $cookieHeader = sprintf(
                "auth_token=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s",
                $token,
                $maxAge,
                $secureFlag
            );

            $response = $response->withAddedHeader('Set-Cookie', trim($cookieHeader, '; '));

            // Handle Remember Me
            if ($rememberMe) {
                $rememberCookieValue = $this->rememberMeService->issue($adminId);
                $rememberHeader = sprintf(
                    "remember_me=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s",
                    $rememberCookieValue,
                    2592000, // 30 Days
                    $secureFlag
                );
                $response = $response->withAddedHeader('Set-Cookie', trim($rememberHeader, '; '));
            }

            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        } catch (AuthStateException $e) {
            if ($e->getMessage() === 'Identifier is not verified.') {
                 return $response
                    ->withHeader('Location', '/verify-email?email=' . urlencode($dto->email))
                    ->withStatus(302);
            }
            return $this->view->render($response, 'login.twig', ['error' => 'Authentication failed.']);
        } catch (InvalidCredentialsException $e) {
            return $this->view->render($response, 'login.twig', ['error' => 'Authentication failed.']);
        }
    }
}
