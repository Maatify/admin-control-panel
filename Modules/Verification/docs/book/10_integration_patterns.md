# Chapter 10: Integration Patterns

This chapter provides real-world examples of how to integrate the `Maatify\Verification` module into a larger application architecture.

## Pattern 1: Email Verification Flow

This is the most common use case.

### Step 1: Request Verification

A user submits an email address. The application needs to verify they own it.

```php
use Maatify\Verification\Domain\Contracts\VerificationCodeGeneratorInterface;
use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

class EmailVerificationController
{
    public function __construct(
        private VerificationCodeGeneratorInterface $generator,
        private EmailServiceInterface $emailService, // Application-specific
        private UserRepositoryInterface $userRepository // Application-specific
    ) {}

    public function requestVerification(string $email, string $clientIp)
    {
        // 1. Generate the secure verification code (invalidates old ones)
        $generated = $this->generator->generate(
            IdentityTypeEnum::Email,
            $email,
            VerificationPurposeEnum::EmailVerification,
            $clientIp
        );

        // 2. Transmit the PLAIN TEXT code to the user via an external service
        $this->emailService->sendVerificationEmail($email, $generated->plainCode);

        return ['message' => 'Verification code sent to email.'];
    }
}
```

### Step 2: Validate Verification

The user receives the email and submits the 6-digit code.

```php
use Maatify\Verification\Domain\Contracts\VerificationCodeValidatorInterface;
use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

class EmailVerificationController
{
    public function __construct(
        private VerificationCodeValidatorInterface $validator,
        private UserRepositoryInterface $userRepository // Application-specific
    ) {}

    public function submitVerification(string $email, string $code, string $clientIp)
    {
        // 1. Validate the provided code against the repository (handles attempts/expiry)
        $result = $this->validator->validate(
            IdentityTypeEnum::Email,
            $email,
            VerificationPurposeEnum::EmailVerification,
            $code,
            $clientIp
        );

        if (!$result->success) {
            // Do NOT reveal why (expired vs wrong vs lockout). Keep it generic.
            throw new \RuntimeException('Invalid or expired verification code.');
        }

        // 2. The code was valid and marked used. Now perform the application domain action.
        $this->userRepository->markEmailAsVerified($email);

        return ['message' => 'Email verified successfully.'];
    }
}
```

## Pattern 2: Magic Link (Code-Only Validation)

In some scenarios, a user clicks a link containing the code directly (`https://example.com/verify?code=123456`), and the application doesn't immediately know *which* email is being verified until the code is checked.

The module provides `validateByCode` for this exact scenario.

```php
use Maatify\Verification\Domain\Contracts\VerificationCodeValidatorInterface;

class MagicLinkController
{
    public function __construct(
        private VerificationCodeValidatorInterface $validator,
        private UserRepositoryInterface $userRepository
    ) {}

    public function verifyLink(string $code, string $clientIp)
    {
        // 1. Validate the code directly (hashes input and looks up)
        $result = $this->validator->validateByCode($code, $clientIp);

        if (!$result->success) {
            throw new \RuntimeException('Invalid or expired verification link.');
        }

        // 2. Extract the identity from the successful VerificationResult DTO
        $identityType = $result->identityType; // e.g., IdentityTypeEnum::Email
        $identityId = $result->identityId;     // e.g., 'user@example.com'
        $purpose = $result->purpose;           // e.g., VerificationPurposeEnum::EmailVerification

        // 3. Process based on the retrieved context
        if ($identityType === IdentityTypeEnum::Email && $purpose === VerificationPurposeEnum::EmailVerification) {
            $this->userRepository->markEmailAsVerified($identityId);
        }

        return ['message' => 'Link verified successfully.'];
    }
}
```

## Pattern 3: IP Auditing Integration

The module is built to track the source IPs of both creation and usage. If your application uses a context object or middleware to resolve IPs, ensure you pass it down.

```php
use Maatify\AdminKernel\Context\RequestContext;
use Psr\Http\Message\ServerRequestInterface;

// Inside a controller/middleware
$ip = $request->getAttribute(RequestContext::class)?->getIpAddress();

// Pass the IP to generation
$generator->generate(..., ..., ..., $ip);

// Pass the IP to validation
$validator->validate(..., ..., ..., $code, $ip);
```

This allows the application to later build security reports (e.g., "Show me all active codes generated from IP X" or "Detect if codes generated in country A are being validated in country B").