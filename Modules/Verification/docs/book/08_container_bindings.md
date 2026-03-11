# Chapter 8: Container Bindings

The `Maatify\Verification` module is designed to be easily injected into any PHP application using a PSR-11 compliant Dependency Injection (DI) container. The `Bootstrap` layer provides a standardized way to register all necessary interface-to-implementation mappings.

## `VerificationBindings` Class

The primary entry point for integration is the `VerificationBindings` static class located in the `Maatify\Verification\Bootstrap` namespace.

```php
use DI\ContainerBuilder;
use Maatify\Verification\Bootstrap\VerificationBindings;

$builder = new ContainerBuilder();
VerificationBindings::register($builder);
$container = $builder->build();
```

The `register()` method configures the following mappings:

1.  **Repository:** Binds `VerificationCodeRepositoryInterface` to `PdoVerificationCodeRepository`.
    *   *Requirement:* The container must already have a configured `PDO` instance and a `Maatify\SharedCommon\Contracts\ClockInterface` instance available.

2.  **Policy Resolver:** Binds `VerificationCodePolicyResolverInterface` to the default `VerificationCodePolicyResolver`.

3.  **Generator:** Binds `VerificationCodeGeneratorInterface` to `VerificationCodeGenerator`.
    *   It automatically injects the resolved Repository, Policy Resolver, and Clock.

4.  **Validator:** Binds `VerificationCodeValidatorInterface` to `VerificationCodeValidator`.
    *   It automatically injects the resolved Repository and Clock.

## Requirements for the Application Container

Before calling `VerificationBindings::register($builder)`, your application must ensure that the following dependencies are available in the DI container:

1.  **`PDO`**: A configured connection to your database (if using the default `PdoVerificationCodeRepository`).
2.  **`Maatify\SharedCommon\Contracts\ClockInterface`**: An implementation of the clock contract (e.g., `Maatify\SharedCommon\SystemClock`).

```php
// Application-level DI configuration (e.g., in your framework's bootstrap)
$builder->addDefinitions([
    PDO::class => function () {
        return new PDO(
            'mysql:host=localhost;dbname=test',
            'user',
            'pass',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    },
    \Maatify\SharedCommon\Contracts\ClockInterface::class => \DI\get(\Maatify\SharedCommon\SystemClock::class),
]);
```

## Customizing Bindings

The `VerificationBindings::register()` method provides a quick start. However, if you need to swap out implementations (e.g., using a custom repository or policy resolver), you can simply override the definitions *after* calling `register()`, or write your own binding logic entirely.

```php
// 1. Register default bindings
VerificationBindings::register($builder);

// 2. Override with custom implementations
$builder->addDefinitions([
    // Replace PDO with a Redis repository
    VerificationCodeRepositoryInterface::class => \DI\autowire(RedisVerificationCodeRepository::class),

    // Replace the default policy resolver
    VerificationCodePolicyResolverInterface::class => \DI\autowire(CustomPolicyResolver::class),
]);
```

By decoupling the instantiation logic from the domain services, the module remains completely framework-agnostic while still offering a straightforward integration path via PHP-DI.