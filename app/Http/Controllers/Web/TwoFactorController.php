<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\Enum\Scope;
use App\Domain\Service\StepUpService;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Throwable;

readonly class TwoFactorController
{
    public function __construct(
        private StepUpService $stepUpService,
        private TotpServiceInterface $totpService,
        private Twig $view,
        private HttpTelemetryRecorderFactory $telemetryFactory
    ) {
    }

    public function setup(Request $request, Response $response): Response
    {
        $secret = $this->totpService->generateSecret();
        return $this->view->render($response, '2fa-setup.twig', ['secret' => $secret]);
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
        $adminId = $adminContext->adminId;

        $sessionId = $this->getSessionIdFromRequest($request);
        if ($sessionId === null) {
            $response->getBody()->write('Session Required');
            return $response->withStatus(401);
        }

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException("Request context missing");
        }

        $enabled = $this->stepUpService->enableTotp($adminId, $sessionId, $secret, $code, $context);

        // Telemetry (best-effort): Web UI 2FA setup mutation
        try {
            $this->telemetryFactory
                ->admin($context)
                ->record(
                    $adminId,
                    TelemetryEventTypeEnum::RESOURCE_MUTATION,
                    $enabled ? TelemetrySeverityEnum::INFO : TelemetrySeverityEnum::WARN,
                    [
                        'action' => '2fa_setup',
                        'result' => $enabled ? 'success' : 'failure',
                    ]
                );
        } catch (Throwable) {
            // swallow — telemetry must never affect request flow
        }

        if ($enabled) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        return $this->view->render($response, '2fa-setup.twig', ['error' => 'Invalid code', 'secret' => $secret]);
    }

    public function verify(Request $request, Response $response): Response
    {
        $template = $request->getAttribute('template');
        if (!is_string($template)) {
            $template = '2fa-verify.twig';
        }

        // ADDITIVE START
        $scope = $this->resolveRequestedScope($request);
        $returnTo = $this->resolveReturnTo($request);
        // ADDITIVE END

        return $this->view->render($response, $template, [
            // ADDITIVE
            'scope' => $scope->value,
            'return_to' => $returnTo,
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
        $adminId = $adminContext->adminId;

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
        $returnTo = $this->resolveReturnTo($request);
        // ADDITIVE END

//        $result = $this->stepUpService->verifyTotp($adminId, $sessionId, $code, $context, Scope::LOGIN);
        $result = $this->stepUpService->verifyTotp(
            $adminId,
            $sessionId,
            $code,
            $context,
            $requestedScope
        );

        // Telemetry (best-effort): Web UI step-up verification
        try {
            $this->telemetryFactory
                ->admin($context)
                ->record(
                    $adminId,
                    $result->success ? TelemetryEventTypeEnum::AUTH_STEPUP_SUCCESS : TelemetryEventTypeEnum::AUTH_STEPUP_FAILURE,
                    $result->success ? TelemetrySeverityEnum::INFO : TelemetrySeverityEnum::WARN,
                    [
                        'scope' => $requestedScope->value,
                        'method' => 'totp',
                        'result' => $result->success ? 'success' : 'failure',
                        'error_reason' => $result->success ? null : ($result->errorReason ?? 'unknown'),
                    ]
                );
        } catch (Throwable) {
            // swallow — telemetry must never affect request flow
        }

        if ($result->success) {
            // ADDITIVE START
            if ($returnTo !== null && $returnTo !== '') {
                return $response->withHeader('Location', $returnTo)->withStatus(302);
            }
            // ADDITIVE END

            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }


        return $this->view->render($response, $template, ['error' => $result->errorReason ?? 'Invalid code']);
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

    private function resolveReturnTo(Request $request): ?string
    {
        // Priority:
        // 1) POST body
        // 2) Query string

        $data = $request->getParsedBody();
        if (is_array($data) && isset($data['return_to']) && is_string($data['return_to'])) {
            return $this->normalizeReturnTo($data['return_to']);
        }

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['return_to']) && is_string($queryParams['return_to'])) {
            return $this->normalizeReturnTo($queryParams['return_to']);
        }

        return null;
    }

    private function normalizeReturnTo(string $returnTo): ?string
    {
        $returnTo = trim($returnTo);
        if ($returnTo === '') {
            return null;
        }

        if (!str_starts_with($returnTo, '/')) {
            return null;
        }

        if (str_starts_with($returnTo, '//')) {
            return null;
        }

        if (str_contains($returnTo, "\r") || str_contains($returnTo, "\n")) {
            return null;
        }

        $parts = parse_url($returnTo);
        if ($parts === false) {
            return null;
        }

        if (isset($parts['scheme']) || isset($parts['host'])) {
            return null;
        }

        return $returnTo;
    }

}
