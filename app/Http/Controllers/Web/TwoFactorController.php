<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Contracts\TotpServiceInterface;
use App\Context\RequestContext;
use App\Domain\Enum\Scope;
use App\Domain\Service\StepUpService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class TwoFactorController
{
    public function __construct(
        private StepUpService $stepUpService,
        private TotpServiceInterface $totpService,
        private Twig $view
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
             return $this->view->render($response, '2fa-setup.twig', ['error' => 'Invalid input', 'secret' => is_string($secret) ? $secret : '']);
        }

        $adminId = $request->getAttribute('admin_id');
        if (!is_int($adminId)) {
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

        if ($this->stepUpService->enableTotp($adminId, $sessionId, $secret, $code, $context)) {
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
        return $this->view->render($response, $template);
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

        $adminId = $request->getAttribute('admin_id');
        if (!is_int($adminId)) {
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

        $result = $this->stepUpService->verifyTotp($adminId, $sessionId, $code, $context, Scope::LOGIN);

        if ($result->success) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        return $this->view->render($response, $template, ['error' => $result->errorReason ?? 'Invalid code']);
    }

    private function getSessionIdFromRequest(Request $request): ?string
    {
        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token'])) {
            return (string)$cookies['auth_token'];
        }

        return null;
    }
}
