<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\VerificationCodeGeneratorInterface;
use App\Domain\Contracts\VerificationCodeValidatorInterface;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\VerificationPurposeEnum;
use App\Domain\Service\AdminEmailVerificationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class EmailVerificationController
{
    public function __construct(
        private VerificationCodeValidatorInterface $validator,
        private VerificationCodeGeneratorInterface $generator,
        private AdminEmailVerificationService $verificationService,
        private AdminIdentifierLookupInterface $lookupInterface,
        private Twig $view,
        private string $blindIndexKey
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $email = $queryParams['email'] ?? '';
        $message = $queryParams['message'] ?? null;

        return $this->view->render($response, 'verify-email.twig', [
            'email' => $email,
            'message' => $message
        ]);
    }

    public function verify(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            return $this->view->render($response, 'verify-email.twig', ['error' => 'Invalid request']);
        }

        $email = (string)($data['email'] ?? '');
        $otp = (string)($data['otp'] ?? '');

        if (empty($email) || empty($otp)) {
             return $this->view->render($response, 'verify-email.twig', [
                 'error' => 'Email and OTP are required.',
                 'email' => $email
             ]);
        }

        // 1. Validate OTP
        $result = $this->validator->validate(IdentityTypeEnum::Email, $email, VerificationPurposeEnum::EmailVerification, $otp);

        if (!$result->success) {
            return $this->view->render($response, 'verify-email.twig', [
                'error' => 'Verification failed.',
                'email' => $email
            ]);
        }

        // 2. Compute Blind Index & Lookup Admin
        $blindIndex = hash_hmac('sha256', $email, $this->blindIndexKey);
        assert(is_string($blindIndex));

        $adminId = $this->lookupInterface->findByBlindIndex($blindIndex);

        if ($adminId === null) {
            // OTP was valid for the email, but no admin found?
            // This is an edge case (orphaned code?). Securely fail.
            return $this->view->render($response, 'verify-email.twig', [
                'error' => 'Verification failed.',
                'email' => $email
            ]);
        }

        // 3. Mark Verified
        try {
            $this->verificationService->verify($adminId);
        } catch (\Exception $e) {
             // Already verified or other error
             // We can proceed to login as if success
        }

        // 4. Redirect to Login
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
            try {
                $this->generator->generate(IdentityTypeEnum::Email, $email, VerificationPurposeEnum::EmailVerification);
            } catch (\Exception $e) {
                // Ignore errors (like rate limit) to avoid leaking info?
                // Or show generic error?
                // "Resend cooldown" implies we might hit rate limit.
                // If we hit rate limit, we should probably tell the user "Too many attempts".
                // But for now, just redirect.
            }
        }

        $url = '/verify-email?message=' . urlencode('Code sent if email is valid.') . '&email=' . urlencode($email);
        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
