<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Application\Verification\VerificationNotificationDispatcherInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\VerificationCodeGeneratorInterface;
use App\Domain\Contracts\VerificationCodeValidatorInterface;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\VerificationFailureReasonEnum;
use App\Domain\Enum\VerificationPurposeEnum;
use App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO;
use App\Domain\SecurityEvents\Enum\SecurityEventActorTypeEnum;
use App\Domain\SecurityEvents\Recorder\SecurityEventRecorderInterface;
use App\Domain\Service\AdminEmailVerificationService;
use App\Context\RequestContext;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
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
        private SecurityEventRecorderInterface $securityEvents,
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private VerificationNotificationDispatcherInterface $verificationDispatcher
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
            return $this->view->render($response, $template, [
                'error' => 'Invalid request'
            ]);
        }

        $email = (string)($data['email'] ?? '');
        $otp   = (string)($data['otp'] ?? '');

        if ($email === '' || $otp === '') {
            return $this->view->render($response, $template, [
                'error' => 'Email and OTP are required.',
                'email' => $email
            ]);
        }

        /** @var RequestContext|null $context */
        $context = $request->getAttribute(RequestContext::class);

        /**
         * 1️⃣ Resolve subject first (email → adminId)
         */
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($email);
        $adminEmailIdentifierDTO = $this->lookupInterface->findByBlindIndex($blindIndex);

        if ($adminEmailIdentifierDTO === null) {
            // Security Event: failed verification (no subject resolved)
            $this->securityEvents->record(
                new SecurityEventRecordDTO(
                    actorType: SecurityEventActorTypeEnum::ADMIN,
                    actorId: null,
                    eventType: SecurityEventTypeEnum::EMAIL_VERIFICATION_SUBJECT_NOT_FOUND,
                    severity: SecurityEventSeverityEnum::WARNING,

                    requestId: $context?->requestId,
                    routeName: $context?->routeName,
                    ipAddress: $context?->ipAddress,
                    userAgent: $context?->userAgent,

                    metadata: [
                        'reason' => VerificationFailureReasonEnum::INVALID_OTP->value,
                        'email'  => $email,
                    ]
                )
            );

            return $this->view->render($response, $template, [
                'error' => 'Verification failed.',
                'email' => $email
            ]);
        }

        $adminId = $adminEmailIdentifierDTO->adminId;
        /**
         * 2️⃣ Validate OTP bound to resolved identity
         */
        $result = $this->validator->validate(
            IdentityTypeEnum::Admin,
            (string)$adminId,
            VerificationPurposeEnum::EmailVerification,
            $otp
        );

        if (!$result->success) {
            // Security Event: invalid / expired / exceeded attempts
            $this->securityEvents->record(
                new SecurityEventRecordDTO(
                    actorType: SecurityEventActorTypeEnum::ADMIN,
                    actorId: $adminId,
                    eventType: SecurityEventTypeEnum::EMAIL_VERIFICATION_FAILED,
                    severity: SecurityEventSeverityEnum::WARNING,

                    requestId: $context?->requestId,
                    routeName: $context?->routeName,
                    ipAddress: $context?->ipAddress,
                    userAgent: $context?->userAgent,

                    metadata: [
                        'reason' => VerificationFailureReasonEnum::INVALID_OTP->value,
                    ]
                )
            );

            return $this->view->render($response, $template, [
                'error' => 'Verification failed.',
                'email' => $email
            ]);
        }

        /**
         * 3️⃣ Mark email as verified
         * (authoritative state change happens inside the service)
         */
        try {
            if (!$context instanceof RequestContext) {
                throw new \RuntimeException('Request context missing');
            }

            $this->verificationService->selfVerify($adminEmailIdentifierDTO->emailId, $context);
        } catch (\Throwable $e) {
            // Idempotent behavior:
            // - already verified
            // - no user-visible impact
            // No Security Event, no Audit
        }

        /**
         * 4️⃣ Redirect to login
         */
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
        $email = '';

        if (is_array($data)) {
            $email = (string)($data['email'] ?? '');
        }

        if ($email !== '') {
            $blindIndex = $this->cryptoService->deriveEmailBlindIndex($email);
            $adminEmailIdentifierDTO = $this->lookupInterface->findByBlindIndex($blindIndex);

            if ($adminEmailIdentifierDTO !== null) {
                try {
                    // ✅ توليد واحد فقط
                    $generated = $this->generator->generate(
                        IdentityTypeEnum::Admin,
                        (string)$adminEmailIdentifierDTO->adminId,
                        VerificationPurposeEnum::EmailVerification
                    );

                    // ✅ dispatch مرتبط بالتوليد
                    $this->verificationDispatcher->dispatch(
                        identityType: IdentityTypeEnum::Admin,
                        identityId: (string)$adminEmailIdentifierDTO->adminId,
                        purpose: VerificationPurposeEnum::EmailVerification,
                        recipient: $email,
                        plainCode: $generated->plainCode,
                        context: [
                            'expires_in' => 600,
                        ],
                        language: 'en'
                    );
                } catch (\Throwable $e) {
                    // Best-effort only:
                    // - rate limit
                    // - resend cooldown
                    // - queue failure
                    // ❌ لا Security Event
                    // ❌ لا Audit
                    // ❌ لا PSR-3 (expected behavior)
                }
            }
        }

        $url = '/verify-email?message='
               . urlencode('Code sent if email is valid.')
               . '&email=' . urlencode($email);

        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
