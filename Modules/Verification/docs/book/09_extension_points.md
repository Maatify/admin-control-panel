# Chapter 9: Extension Points

The `Maatify\Verification` module is designed to be highly extensible without requiring modifications to the core domain services. This is achieved through strictly defined interfaces.

## 1. Custom Policy Resolvers

The most common extension point is modifying the rules for different verification purposes (e.g., changing the TTL or max attempts).

Instead of modifying the default `VerificationCodePolicyResolver`, you should create a new class that implements `VerificationCodePolicyResolverInterface`.

```php
use Maatify\Verification\Domain\Contracts\VerificationCodePolicyResolverInterface;
use Maatify\Verification\Domain\DTO\VerificationPolicy;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

class MyCustomPolicyResolver implements VerificationCodePolicyResolverInterface
{
    public function resolve(VerificationPurposeEnum $purpose): VerificationPolicy
    {
        return match ($purpose) {
            VerificationPurposeEnum::EmailVerification => new VerificationPolicy(
                ttlSeconds: 1800, // 30 minutes instead of 10
                maxAttempts: 5,   // 5 attempts instead of 3
                resendCooldownSeconds: 120 // 2 minute cooldown
            ),
            VerificationPurposeEnum::TelegramChannelLink => new VerificationPolicy(
                ttlSeconds: 600, // 10 minutes instead of 5
                maxAttempts: 3,
                resendCooldownSeconds: 60
            ),
            // Default fallback if a new enum is added
            default => new VerificationPolicy(300, 3, 60),
        };
    }
}
```

Bind this custom resolver in your dependency injection container:

```php
$builder->addDefinitions([
    VerificationCodePolicyResolverInterface::class => \DI\autowire(MyCustomPolicyResolver::class),
]);
```

## 2. Extending Enums

If your application requires new identity types or verification purposes, you cannot directly extend the existing `IdentityTypeEnum` or `VerificationPurposeEnum` because PHP Enums are final by design.

**Option A: Forking the Module (Not Recommended)**
Modifying the Enums directly within the `maatify/verification` package breaks the upgrade path.

**Option B: Mapping at the Boundary (Recommended)**
If you need a new purpose (e.g., `SmsVerification`), map it to an existing enum at the boundary of your application if possible, or wait for an upstream addition if the module is maintained externally.

*Note: In the current version of the module, Enums are strictly typed in the interfaces. Future versions may transition to an `Interface`-backed Enum system to allow full extensibility without forking.*

## 3. Custom Repositories

The default `PdoVerificationCodeRepository` uses standard SQL. For high-traffic applications, you might want to store verification codes in an in-memory datastore like Redis to reduce database load and leverage native TTLs.

To do this, create a class implementing `VerificationCodeRepositoryInterface`.

```php
use Maatify\Verification\Domain\Contracts\VerificationCodeRepositoryInterface;
use Maatify\Verification\Domain\DTO\VerificationCode;
use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

class RedisVerificationCodeRepository implements VerificationCodeRepositoryInterface
{
    public function __construct(private \Redis $redis) {}

    public function store(VerificationCode $code): void
    {
        $key = $this->buildKey($code->identityType, $code->identityId, $code->purpose);
        // Serialize the DTO and store with TTL
        $this->redis->setex($key, $code->expiresAt->getTimestamp() - time(), serialize($code));
    }

    public function findActive(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose): ?VerificationCode
    {
         $key = $this->buildKey($identityType, $identityId, $purpose);
         $data = $this->redis->get($key);
         return $data ? unserialize($data) : null;
    }

    // ... implement other required methods (findByCodeHash, expire, incrementAttempts, etc.)
}
```

Bind the custom repository in your DI container:

```php
$builder->addDefinitions([
    VerificationCodeRepositoryInterface::class => \DI\autowire(RedisVerificationCodeRepository::class),
]);
```

The core `VerificationCodeValidator` and `VerificationCodeGenerator` will seamlessly use the Redis implementation without any changes to their business logic.