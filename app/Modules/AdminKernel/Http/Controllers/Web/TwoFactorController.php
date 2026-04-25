<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Web;

use Maatify\AdminKernel\Application\Auth\DTO\TwoFactorSetupRequestDTO;
use Maatify\AdminKernel\Application\Auth\DTO\TwoFactorVerifyRequestDTO;
use Maatify\AdminKernel\Application\Auth\TwoFactorEnrollmentService;
use Maatify\AdminKernel\Application\Auth\TwoFactorVerificationService;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Enum\Scope;
use Maatify\AdminKernel\Domain\Contracts\Auth\RedirectTokenProviderInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class TwoFactorController
{
    public function __construct(
        private TwoFactorEnrollmentService $enrollmentService,
        private TwoFactorVerificationService $verificationService,
        private Twig $view,
        private RedirectTokenProviderInterface $redirectTokenProvider,
    ) {
    }

    public function setup(Request $request, Response $response): Response
    {
        $page = $this->enrollmentService->buildSetupPage();
        return $this->view->render($response, '2fa-setup.twig', ['secret' => $page->secret]);
    }

    public function doSetup(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            return $this->view->render($response, '2fa-setup.twig', ['error' => 'Invalid request']);
        }

        $secret = $data['secret'] ?? '';
        $code = $data['code'] ?? '';

        if (!is_string($secret) || !is_string($code)) {
            return $this->view->render($response, '2fa-setup.twig', [
                'error' => 'Invalid input',
                'secret' => is_string($secret) ? $secret : '',
            ]);
        }

        $adminContext = $request->getAttribute(AdminContext::class);
        if (!$adminContext instanceof AdminContext) {
            $response->getBody()->write('Unauthorized');
            return $response->withStatus(401);
        }

        $sessionId = $this->getSessionIdFromRequest($request);
        if ($sessionId === null) {
            $response->getBody()->write('Session Required');
            return $response->withStatus(401);
        }

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException("Request context missing");
        }

        $result = $this->enrollmentService->enableTotp(
            new TwoFactorSetupRequestDTO(
                adminId: $adminContext->adminId,
                sessionId: $sessionId,
                secret: $secret,
                code: $code,
                requestContext: $context,
            )
        );

        if ($result->success) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        return $this->view->render($response, '2fa-setup.twig', [
            'error' => 'Invalid code',
            'secret' => $result->secret,
        ]);
    }

    public function verify(Request $request, Response $response): Response
    {
        $template = $request->getAttribute('template');
        if (!is_string($template)) {
            $template = '2fa-verify.twig';
        }

        // ADDITIVE START
        $scope = $this->resolveRequestedScope($request);
$redirectToken = $this->resolveRedirectToken($request);
        // ADDITIVE END

        return $this->view->render($response, $template, [
            // ADDITIVE
            'scope' => $scope->value,
            'r' => $redirectToken,
        ]);
    }

    public function doVerify(Request $request, Response $response): Response
    {
        $template = $request->getAttribute('template');
        if (!is_string($template)) {
            $template = '2fa-verify.twig';
        }

        $data = $request->getParsedBody();
        if (!is_array($data)) {
            return $this->view->render($response, $template, ['error' => 'Invalid request']);
        }

        $code = $data['code'] ?? '';
        if (!is_string($code)) {
            return $this->view->render($response, $template, ['error' => 'Invalid input']);
        }

        $adminContext = $request->getAttribute(AdminContext::class);
        if (!$adminContext instanceof AdminContext) {
            $response->getBody()->write('Unauthorized');
            return $response->withStatus(401);
        }

        $sessionId = $this->getSessionIdFromRequest($request);
        if ($sessionId === null) {
            $response->getBody()->write('Session Required');
            return $response->withStatus(401);
        }

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException("Request context missing");
        }

        // ADDITIVE START
        $requestedScope = $this->resolveRequestedScope($request);
        $redirectToken = $this->resolveRedirectToken($request);
        // ADDITIVE END

        $result = $this->verificationService->verifyTotp(
            new TwoFactorVerifyRequestDTO(
                adminId: $adminContext->adminId,
                sessionId: $sessionId,
                code: $code,
                requestedScope: $requestedScope,
                requestContext: $context,
            )
        );

        if ($result->success) {
            // ADDITIVE START
            if ($redirectToken !== null) {
                $parsed = $this->redirectTokenProvider->verifyAndParse($redirectToken);
                if ($parsed !== null) {
                    return $response->withHeader('Location', $parsed->path)->withStatus(302);
                }
            }
            // ADDITIVE END

            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        return $this->view->render($response, $template, [
            'error' => $result->errorReason ?? 'Invalid code',
            'scope' => $requestedScope->value,
            'r' => $redirectToken,
        ]);
    }

    private function getSessionIdFromRequest(Request $request): ?string
    {
        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token'])) {
            return (string) $cookies['auth_token'];
        }

        return null;
    }

    private function resolveRequestedScope(Request $request): Scope
    {
        // Priority:
        // 1) POST body
        // 2) Query string
        // 3) Default LOGIN (backward compatible)

        $data = $request->getParsedBody();
        if (is_array($data) && isset($data['scope']) && is_string($data['scope'])) {
            try {
                return Scope::from($data['scope']);
            } catch (\ValueError) {
                // ignore invalid scope, fallback to LOGIN
            }
        }

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['scope']) && is_string($queryParams['scope'])) {
            try {
                return Scope::from($queryParams['scope']);
            } catch (\ValueError) {
                // ignore invalid scope, fallback to LOGIN
            }
        }

        return Scope::LOGIN;
    }

    private function resolveRedirectToken(Request $request): ?string
    {
        $data = $request->getParsedBody();
        if (is_array($data) && isset($data['r']) && is_string($data['r'])) {
            $token = trim($data['r']);
            return $token === '' ? null : $token;
        }

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['r']) && is_string($queryParams['r'])) {
            $token = trim($queryParams['r']);
            return $token === '' ? null : $token;
        }

        return null;
    }
}
