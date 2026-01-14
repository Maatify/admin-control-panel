<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\VerificationCodeGeneratorInterface;
use App\Domain\Contracts\VerificationCodeValidatorInterface;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\VerificationFailureReasonEnum;
use App\Context\RequestContext;
use App\Domain\Enum\VerificationPurposeEnum;
use App\Domain\Service\AdminEmailVerificationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

readonly class EmailVerificationController
{
    public function __construct(
        private VerificationCodeValidatorInterface $validator,
        private VerificationCodeGeneratorInterface $generator,
        private AdminEmailVerificationService $verificationService,
        private AdminIdentifierLookupInterface $lookupInterface,
        private Twig $view,
        private LoggerInterface $logger,
        private AdminIdentifierCryptoServiceInterface $cryptoService
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $template = $request->getAttribute('template');
        if (!is_string($template)) {
            $template = 'verify-email.twig';
        }

        $queryParams = $request->getQueryParams();
        $email = $queryParams['email'] ?? '';
        $message = $queryParams['message'] ?? null;

        return $this->view->render($response, $template, [
            'email' => $email,
            'message' => $message
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
            return $this->view->render($response, $template, ['error' => 'Invalid request']);
        }

        $email = (string)($data['email'] ?? '');
        $otp = (string)($data['otp'] ?? '');

        if (empty($email) || empty($otp)) {
             return $this->view->render($response, $template, [
                 'error' => 'Email and OTP are required.',
                 'email' => $email
             ]);
        }

        // 1. Validate OTP (Identity-based)
        $result = $this->validator->validateByCode($otp);

        if (!$result->success) {
            $this->logger->warning('Email verification failed', [
                'reason' => VerificationFailureReasonEnum::INVALID_OTP->value,
                'identity_type' => $result->identityType?->value,
                'identity_id' => $result->identityId,
                'purpose' => $result->purpose?->value,
            ]);
            return $this->view->render($response, $template, [
                'error' => 'Verification failed.',
                'email' => $email
            ]);
        }

        // 2. Check Purpose
        if ($result->purpose !== VerificationPurposeEnum::EmailVerification) {
            $this->logger->warning('Email verification failed', [
                'reason' => VerificationFailureReasonEnum::OTP_WRONG_PURPOSE->value,
                'identity_type' => $result->identityType?->value,
                'identity_id' => $result->identityId,
                'purpose' => $result->purpose?->value,
            ]);
            return $this->view->render($response, $template, [
                'error' => 'Verification failed.',
                'email' => $email
            ]);
        }
        assert($result->purpose !== null);

        // 3. Check Identity Type
        if ($result->identityType !== IdentityTypeEnum::Admin) {
            $this->logger->warning('Email verification failed', [
                'reason' => VerificationFailureReasonEnum::IDENTITY_MISMATCH->value,
                'identity_type' => $result->identityType?->value,
                'identity_id' => $result->identityId,
                'purpose' => $result->purpose->value,
            ]);
            return $this->view->render($response, $template, [
                'error' => 'Verification failed.',
                'email' => $email
            ]);
        }
        assert($result->identityType !== null);

        // 4. Validate Identity ID Format
        if ($result->identityId === null || !is_numeric($result->identityId)) {
            $this->logger->warning('Email verification failed', [
                'reason' => VerificationFailureReasonEnum::INVALID_IDENTITY_ID->value,
                'identity_type' => $result->identityType->value,
                'identity_id' => $result->identityId,
                'purpose' => $result->purpose->value,
            ]);
            return $this->view->render($response, $template, [
                'error' => 'Verification failed.',
                'email' => $email
            ]);
        }

        $adminId = (int)$result->identityId;

        // 5. Mark Verified
        try {
            $context = $request->getAttribute(RequestContext::class);
            if (!$context instanceof RequestContext) {
                 throw new \RuntimeException("Request context missing");
            }
            $this->verificationService->verify($adminId, $context);
        } catch (\Exception $e) {
             // Already verified or other error
             // We can proceed to login as if success
        }

        // 6. Redirect to Login
        return $response
            ->withHeader('Location', '/login?message=' . urlencode('Email verified. Please login.'))
            ->withStatus(302);
    }

    public function resend(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = '';
        if (is_array($data)) {
            $email = (string)($data['email'] ?? '');
        }

        if (!empty($email)) {
            // 1. Compute Blind Index & Lookup Admin
            $blindIndex = $this->cryptoService->deriveEmailBlindIndex($email);

            $adminId = $this->lookupInterface->findByBlindIndex($blindIndex);

            if ($adminId !== null) {
                try {
                    $this->generator->generate(IdentityTypeEnum::Admin, (string)$adminId, VerificationPurposeEnum::EmailVerification);
                } catch (\Exception $e) {
                    // Ignore errors (like rate limit) to avoid leaking info
                }
            }
        }

        $url = '/verify-email?message=' . urlencode('Code sent if email is valid.') . '&email=' . urlencode($email);
        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
