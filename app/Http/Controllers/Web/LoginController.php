<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Auth\AdminLoginService;
use App\Context\RequestContext;
use App\Domain\DTO\LoginRequestDTO;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\MustChangePasswordException;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class LoginController
{
    public function __construct(
        private AdminLoginService $loginService,
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
        if (!is_array($data) || !isset($data['email'], $data['password'])) {
            return $this->view->render($response, $template, ['error' => 'Invalid request']);
        }

        $dto = new LoginRequestDTO((string)$data['email'], (string)$data['password']);

        try {
            $requestContext = $request->getAttribute(RequestContext::class);
            if (! $requestContext instanceof RequestContext) {
                throw new \RuntimeException('Request Context not present');
            }

            $rememberMeRequested = !empty($data['remember_me']);

            $result = $this->loginService->login(
                dto: $dto,
                requestContext: $requestContext,
                rememberMeRequested: $rememberMeRequested
            );

            // Determine Secure flag based on request scheme (HTTP concern)
            $isSecure = $request->getUri()->getScheme() === 'https';
            $secureFlag = $isSecure ? 'Secure;' : '';

            $cookieHeader = sprintf(
                "auth_token=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s",
                $result->authToken,
                $result->authTokenMaxAgeSeconds,
                $secureFlag
            );
            $cookieHeader = trim($cookieHeader, '; ');
            $response = $response->withHeader('Set-Cookie', $cookieHeader);

            if ($result->rememberMeToken !== null) {
                $rememberMeCookie = sprintf(
                    "remember_me=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s",
                    $result->rememberMeToken,
                    60 * 60 * 24 * 30,
                    $secureFlag
                );
                $response = $response->withAddedHeader('Set-Cookie', $rememberMeCookie);
            }

            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        } catch (MustChangePasswordException $e) {
            return $response
                ->withHeader('Location', '/auth/change-password?email=' . urlencode($dto->email))
                ->withStatus(302);
        } catch (AuthStateException $e) {
            if ($e->reason() === AuthStateException::REASON_NOT_VERIFIED) {
                return $response
                    ->withHeader('Location', '/verify-email?email=' . urlencode($dto->email))
                    ->withStatus(302);
            }

            return $this->view->render($response, $template, ['error' => $e->getMessage()]);
        } catch (InvalidCredentialsException $e) {
            return $this->view->render($response, $template, ['error' => 'Authentication failed.']);
        }
    }
}
