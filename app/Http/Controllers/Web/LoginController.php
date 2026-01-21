<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\DTO\LoginRequestDTO;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\MustChangePasswordException;
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
            $requestContext = $request->getAttribute(RequestContext::class);
            if (! $requestContext instanceof RequestContext) {
                throw new \RuntimeException('Request Context not present');
            }

            // We get the token.
            $result = $this->authService->login($blindIndex, $dto->password, $requestContext);

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
                $rememberMeToken = $this->rememberMeService->issue((int)$session['admin_id'], $requestContext);

                $rememberMeCookie = sprintf(
                    "remember_me=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s",
                    $rememberMeToken,
                    60 * 60 * 24 * 30, // 30 days
                    $secureFlag
                );

                $response = $response->withAddedHeader('Set-Cookie', $rememberMeCookie);
            }

            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        catch (MustChangePasswordException $e) {
            return $response
                ->withHeader(
                    'Location',
                    '/auth/change-password?email=' . urlencode($dto->email)
                )
                ->withStatus(302);

        }
        catch (AuthStateException $e) {

            if ($e->reason() === AuthStateException::REASON_NOT_VERIFIED) {
                return $response
                    ->withHeader(
                        'Location',
                        '/verify-email?email=' . urlencode($dto->email)
                    )
                    ->withStatus(302);
            }

            // أي حالة تانية (SUSPENDED / DISABLED)
            return $this->view->render($response, $template, [
                'error' => $e->getMessage()
            ]);
        }
        catch (InvalidCredentialsException $e) {
            return $this->view->render($response, $template, [
                'error' => 'Authentication failed.'
            ]);
        }

    }
}
