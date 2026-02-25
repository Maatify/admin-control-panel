<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Web;

use Maatify\AdminKernel\Application\Auth\AdminLoginService;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\Abuse\ChallengeWidgetRendererInterface;
use Maatify\AdminKernel\Domain\DTO\LoginRequestDTO;
use Maatify\AdminKernel\Domain\Exception\AuthStateException;
use Maatify\AdminKernel\Domain\Exception\InvalidCredentialsException;
use Maatify\AdminKernel\Domain\Exception\MustChangePasswordException;
use Maatify\AdminKernel\Domain\Security\RedirectToken\RedirectTokenServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class LoginController
{
    public function __construct(
        private AdminLoginService $loginService,
        private Twig $view,
        private ChallengeWidgetRendererInterface $challengeRenderer,
        private RedirectTokenServiceInterface $redirectTokenService
    )
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $template = $request->getAttribute('template');
        if (! is_string($template)) {
            $template = 'login.twig';
        }

        $challengeWidget = null;

        if ($this->challengeRenderer->isEnabled()) {
            $challengeWidget = $this->challengeRenderer->renderWidgetHtml();
        }

        return $this->view->render($response,
            $template,
            [
                'require_challenge' => true,
                'challenge_widget' => $challengeWidget,
            ]
        );
    }

    public function login(Request $request, Response $response): Response
    {
        $template = $request->getAttribute('template');
        if (! is_string($template)) {
            $template = 'login.twig';
        }

        $data = $request->getParsedBody();
        if (! is_array($data) || ! isset($data['email'], $data['password'])) {
            return $this->view->render($response, $template, ['error' => 'Invalid request']);
        }

        $dto = new LoginRequestDTO((string)$data['email'], (string)$data['password']);

        try {
            $requestContext = $request->getAttribute(RequestContext::class);
            if (! $requestContext instanceof RequestContext) {
                throw new \RuntimeException('Request Context not present');
            }

            $rememberMeRequested = ! empty($data['remember_me']);

            $existingDeviceId = $request->getAttribute('abuse_device_id');

            if (! is_string($existingDeviceId) || $existingDeviceId === '') {
                $existingDeviceId = null;
            }

            $requireChallenge = $request->getAttribute('require_challenge') === true;
            $challengePassed  = $request->getAttribute('challenge_passed') === true;

            if ($requireChallenge && ! $challengePassed) {

                $challengeWidget = null;

                $challengeReason = $request->getAttribute('challenge_reason');

                if ($this->challengeRenderer->isEnabled()) {
                    $challengeWidget = $this->challengeRenderer->renderWidgetHtml();
                }

                return $this->view->render(
                    $response,
                    $template,
                    [
                        'error'              => 'Verification required.',
                        'require_challenge'  => true,
                        'challenge_error'    => $request->getAttribute('challenge_reason'),
                        'challenge_widget' => $challengeWidget,
                    ]
                );
            }

            $result = $this->loginService->login(
                dto                : $dto,
                requestContext     : $requestContext,
                rememberMeRequested: $rememberMeRequested,
                existingDeviceId   : $existingDeviceId
            );

            // Determine Secure flag based on request scheme (HTTP concern)
            $isSecure = $request->getUri()->getScheme() === 'https';
            $secureFlag = $isSecure ? 'Secure;' : '';

            // -------------------------------------------------
            // Auth Token Cookie
            // -------------------------------------------------
            $authCookie = sprintf(
                'auth_token=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s',
                $result->authToken,
                $result->authTokenMaxAgeSeconds,
                $secureFlag
            );
            $response = $response->withHeader('Set-Cookie', trim($authCookie, '; '));

            // -------------------------------------------------
            // Remember Me Cookie (optional)
            // -------------------------------------------------
            if ($result->rememberMeToken !== null) {
                $rememberMeCookie = sprintf(
                    'remember_me=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s',
                    $result->rememberMeToken,
                    60 * 60 * 24 * 30,
                    $secureFlag
                );
                $response = $response->withAddedHeader('Set-Cookie', trim($rememberMeCookie, '; '));
            }

            // -------------------------------------------------
            // Abuse Protection Cookies (device + signature)
            // -------------------------------------------------
            if ($result->abuseCookie !== null) {
                // Stable device identifier
                $deviceCookie = sprintf(
                    'abuse_device_id=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s',
                    $result->abuseCookie->deviceId,
                    $result->abuseCookie->deviceTtlSeconds,
                    $secureFlag
                );

                // Short-lived signature
                $signatureCookie = sprintf(
                    'abuse_sig=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%d; %s',
                    $result->abuseCookie->signature,
                    $result->abuseCookie->signatureTtlSeconds,
                    $secureFlag
                );

                $response = $response
                    ->withAddedHeader('Set-Cookie', trim($deviceCookie, '; '))
                    ->withAddedHeader('Set-Cookie', trim($signatureCookie, '; '));
            }

            $redirect = '/dashboard';
            $queryParams = $request->getQueryParams();
            if (isset($queryParams['r']) && is_string($queryParams['r'])) {
                $path = $this->redirectTokenService->verify($queryParams['r']);
                if ($path !== null) {
                    $redirect = $path;
                }
            }

            return $response
                ->withHeader('Location', $redirect)
                ->withStatus(302);
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

            return $this->view->render(
                $response,
                $template,
                ['error' => $e->getMessage()]
            );
        } catch (InvalidCredentialsException $e) {
            $challengeWidget = null;

            $challengeReason = $request->getAttribute('challenge_reason');

            if ($this->challengeRenderer->isEnabled()) {
                $challengeWidget = $this->challengeRenderer->renderWidgetHtml();
            }

            return $this->view->render(
                $response,
                $template,
                [
                    'error'            => 'Authentication failed.',
                    'require_challenge' => true,
                    'challenge_widget' => $challengeWidget,
                    'challenge_error'    => is_string($challengeReason) ? $challengeReason : null,
                ]
            );
        }
    }
}
