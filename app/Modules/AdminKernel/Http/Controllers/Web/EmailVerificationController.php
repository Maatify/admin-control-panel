<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Web;

use Maatify\AdminKernel\Application\Auth\VerifyEmailService;
use Maatify\AdminKernel\Application\Auth\ResendEmailVerificationService;
use Maatify\AdminKernel\Application\Auth\DTO\VerifyEmailRequestDTO;
use Maatify\AdminKernel\Application\Auth\DTO\ResendEmailVerificationRequestDTO;
use Maatify\AdminKernel\Context\RequestContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class EmailVerificationController
{
    public function __construct(
        private Twig $view,
        private VerifyEmailService $verifyEmailService,
        private ResendEmailVerificationService $resendService,
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $template = $request->getAttribute('template');
        if (!is_string($template)) {
            $template = 'verify-email.twig';
        }

        $queryParams = $request->getQueryParams();

        return $this->view->render($response, $template, [
            'email' => (string)($queryParams['email'] ?? ''),
            'message' => $queryParams['message'] ?? null,
        ]);
    }

    public function verify(Request $request, Response $response): Response
    {
        $template = $request->getAttribute('template');
        if (!is_string($template)) {
            $template = 'verify-email.twig';
        }

        $data = $request->getParsedBody();
        if (!is_array($data)) {
            return $this->view->render($response, $template, [
                'error' => 'Invalid request',
            ]);
        }

        $email = (string)($data['email'] ?? '');
        $otp   = (string)($data['otp'] ?? '');

        if ($email === '' || $otp === '') {
            return $this->view->render($response, $template, [
                'error' => 'Email and OTP are required.',
                'email' => $email,
            ]);
        }

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException('RequestContext missing');
        }

        $result = $this->verifyEmailService->verify(
            new VerifyEmailRequestDTO($email, $otp, $context)
        );

        if (!$result->success) {
            return $this->view->render($response, $template, [
                'error' => 'Verification failed.',
                'email' => $email,
            ]);
        }

        return $response
            ->withHeader(
                'Location',
                '/login?message=' . urlencode('Email verified. Please login.')
            )
            ->withStatus(302);
    }

    public function resend(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = is_array($data) ? (string)($data['email'] ?? '') : '';

        $this->resendService->resend(
            new ResendEmailVerificationRequestDTO($email)
        );

        return $response
            ->withHeader(
                'Location',
                '/verify-email?message='
                . urlencode('Code sent if email is valid.')
                . '&email=' . urlencode($email)
            )
            ->withStatus(302);
    }
}
