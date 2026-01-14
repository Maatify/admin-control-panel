<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\ActivityLog\Action\AdminActivityAction;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
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
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private Twig $view,
        private AdminActivityLogService $adminActivityLogService,
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $template = $request->getAttribute('template');
        if (!is_string($template)) {
            $template = 'login.twig';
        }
        return $this->view->render($response, $template);
    }

    public function login(Request $request, Response $response): Response
    {
        $template = $request->getAttribute('template');
        if (!is_string($template)) {
            $template = 'login.twig';
        }

        $data = $request->getParsedBody();
        if (!is_array($data) || !isset($data['email']) || !isset($data['password'])) {
             return $this->view->render($response, $template, ['error' => 'Invalid request']);
        }

        $dto = new LoginRequestDTO((string)$data['email'], (string)$data['password']);

        // Blind Index Calculation
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($dto->email);

        try {
            // We get the token.
            $result = $this->authService->login($blindIndex, $dto->password);

            // 🔹 LOGIN_SUCCESS CONTEXT AUGMENTATION
            // We MUST augment the same request with AdminContext
            $adminContext = new AdminContext($result->adminId);
            $request = $request->withAttribute(AdminContext::class, $adminContext);

            // Read context from request attributes
            /** @var RequestContext $requestContext */
            $requestContext = $request->getAttribute(RequestContext::class);
            /** @var AdminContext $adminContext */
            $adminContext = $request->getAttribute(AdminContext::class);

            // 🔹 Activity Log (SUCCESS)
            $this->adminActivityLogService->log(
                adminContext: $adminContext,
                requestContext: $requestContext,
                action: AdminActivityAction::LOGIN_SUCCESS,
                metadata: [
                    'method' => 'password',
                    'remember_me' => isset($data['remember_me']) && $data['remember_me'] === 'on',
                ]
            );

            $token = $result->token;

            // Fetch session details to align cookie expiration
            $session = $this->sessionRepository->findSession($result->token);
            if ($session === null) {
                // This should practically not happen immediately after creation
                throw new InvalidCredentialsException("Session creation failed.");
            }

            $expiresAt = new DateTimeImmutable($session['expires_at']);
            $now = new DateTimeImmutable();
            $maxAge = $expiresAt->getTimestamp() - $now->getTimestamp();

            // Ensure positive Max-Age
            if ($maxAge < 0) {
                $maxAge = 0;
            }

            // Determine Secure flag based on request scheme
            $isSecure = $request->getUri()->getScheme() === 'https';
            $secureFlag = $isSecure ? 'Secure;' : '';

            $cookieHeader = sprintf(
                "auth_token=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s",
                $token,
                $maxAge,
                $secureFlag
            );

            // Trim trailing semicolon/space if not secure
            $cookieHeader = trim($cookieHeader, '; ');

            $response = $response->withHeader('Set-Cookie', $cookieHeader);

            // Handle Remember Me
            if (isset($data['remember_me']) && $data['remember_me'] === 'on') {
                $rememberMeToken = $this->rememberMeService->issue((int)$session['admin_id']);

                $rememberMeCookie = sprintf(
                    "remember_me=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s",
                    $rememberMeToken,
                    60 * 60 * 24 * 30, // 30 days
                    $secureFlag
                );

                $response = $response->withAddedHeader('Set-Cookie', $rememberMeCookie);
            }

            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        } catch (AuthStateException $e) {
            if ($e->getMessage() === 'Identifier is not verified.') {
                 return $response
                    ->withHeader('Location', '/verify-email?email=' . urlencode($dto->email))
                    ->withStatus(302);
            }
            return $this->view->render($response, $template, ['error' => 'Authentication failed.']);
        } catch (InvalidCredentialsException $e) {
            // Requirement: Generic login error message
            return $this->view->render($response, $template, ['error' => 'Authentication failed.']);
        }
    }
}
