# How to Use

This guide provides practical integration instructions for the `Maatify\Verification` module into your application, using real code patterns from the repository.

## 1. Container Bindings

The easiest way to integrate the module is by utilizing the provided `VerificationBindings` class, which is designed to work with any PHP-DI compatible container builder (`DI\ContainerBuilder`).

It wires up all interfaces to their concrete implementations.

```php
use DI\ContainerBuilder;
use Maatify\Verification\Bootstrap\VerificationBindings;

$builder = new ContainerBuilder();

// Add your global dependencies (PDO, ClockInterface)
$builder->addDefinitions([
    PDO::class => function () {
        return new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
    },
    \Maatify\SharedCommon\Contracts\ClockInterface::class => \DI\get(\Maatify\SharedCommon\SystemClock::class),
]);

// Register Verification bindings
VerificationBindings::register($builder);

$container = $builder->build();
```

## 2. Resolving Services

Once the bindings are registered, you can resolve the main services anywhere in your application (e.g., inside a controller or service).

```php
use Maatify\Verification\Domain\Contracts\VerificationCodeGeneratorInterface;
use Maatify\Verification\Domain\Contracts\VerificationCodeValidatorInterface;

class VerificationController
{
    public function __construct(
        private VerificationCodeGeneratorInterface $generator,
        private VerificationCodeValidatorInterface $validator
    ) {
    }
}
```

## 3. Generating Verification Codes

To generate a new verification code, you must specify the identity type, the identifier itself, and the purpose of the verification. You can optionally provide the client's IP address for auditing.

```php
use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

// Inside your controller action
$generated = $this->generator->generate(
    IdentityTypeEnum::Email,
    'user@example.com',
    VerificationPurposeEnum::EmailVerification,
    $request->getAttribute(\Maatify\AdminKernel\Context\RequestContext::class)?->getIpAddress() // Example IP tracking
);

// $generated->plainCode contains the 6-digit plain text code (e.g., '123456')
// $generated->entity contains the hashed DTO representation ready for storage

// Example: send the code via email
$emailService->sendVerificationCode('user@example.com', $generated->plainCode);
```

### Important Notes on Generation:
* **Invalidation:** Generating a new code automatically expires any previously active codes for the same identity and purpose. This prevents multiple valid codes from existing simultaneously.
* **Security:** The plain text code is only available immediately after generation in the `GeneratedVerificationCode` DTO. It is never stored in plaintext within the repository.

## 4. Validating Verification Codes

When the user submits the code, use the validator to verify it against the hashed representation in storage. You can provide the IP address used during the validation attempt for tracking purposes.

```php
use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

$result = $this->validator->validate(
    IdentityTypeEnum::Email,
    'user@example.com',
    VerificationPurposeEnum::EmailVerification,
    '123456', // The plain code provided by the user
    $request->getAttribute(\Maatify\AdminKernel\Context\RequestContext::class)?->getIpAddress() // Example IP tracking
);

if ($result->success) {
    // The code is valid and has been marked as 'used'
    // Proceed with the domain action (e.g., verifying the user's email)
} else {
    // The code was invalid, expired, or max attempts were exceeded
    // $result->reason may provide further context (though generic for security)
    throw new \RuntimeException('Invalid verification code.');
}
```

Alternatively, you can validate a code directly by its plain text value (without knowing the identity beforehand) if your use case requires it:

```php
$result = $this->validator->validateByCode('123456', '192.168.1.101');

if ($result->success) {
    // You can access the identity details from the result object
    $identityType = $result->identityType;
    $identityId = $result->identityId;
}
```

## 5. Policy Resolution

The `VerificationCodePolicyResolverInterface` handles the configuration for different types of verification (TTL, max attempts, resend cooldown). If you need custom policies, you can provide your own implementation.

```php
use Maatify\Verification\Domain\Contracts\VerificationCodePolicyResolverInterface;
use Maatify\Verification\Domain\DTO\VerificationPolicy;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

class CustomPolicyResolver implements VerificationCodePolicyResolverInterface
{
    public function resolve(VerificationPurposeEnum $purpose): VerificationPolicy
    {
        return match ($purpose) {
            VerificationPurposeEnum::EmailVerification => new VerificationPolicy(
                ttlSeconds: 1800, // 30 minutes
                maxAttempts: 5,
                resendCooldownSeconds: 120
            ),
            // ...
        };
    }
}
```

Then, update your container bindings to use your custom implementation instead of the default `VerificationCodePolicyResolver`.

## 6. Replacing Repository Implementations

The module comes with `PdoVerificationCodeRepository` by default, but you can easily replace it with a Redis, Doctrine, or Eloquent repository by implementing the `VerificationCodeRepositoryInterface`.

```php
use Maatify\Verification\Domain\Contracts\VerificationCodeRepositoryInterface;
use Maatify\Verification\Domain\DTO\VerificationCode;
use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

class RedisVerificationCodeRepository implements VerificationCodeRepositoryInterface
{
    // Implement the interface methods...
    public function store(VerificationCode $code): void { /* ... */ }
    public function findActive(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose): ?VerificationCode { /* ... */ }
    // ...
}
```

And bind it in your DI configuration:

```php
$builder->addDefinitions([
    VerificationCodeRepositoryInterface::class => \DI\autowire(RedisVerificationCodeRepository::class),
]);
```

## 7. Correct Integration Patterns

When integrating the module, ensure you follow these patterns:

1. **Never store plaintext codes:** Rely on the `codeHash` property of the `VerificationCode` DTO.
2. **Use strict Enums:** Use `IdentityTypeEnum` and `VerificationPurposeEnum` to clearly define what the code is verifying.
3. **Handle failures securely:** The validator will securely increment attempts on failure and expire the code when the limit is reached. Don't build separate failure tracking unless absolutely necessary for infrastructure rate-limiting.
4. **Pass IP addresses when possible:** Utilizing the optional `createdIp` and `usedIp` parameters during generation and validation greatly assists in security auditing and identifying brute-force attacks across multiple accounts.