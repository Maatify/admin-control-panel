# Chapter 5: Integration Patterns

This chapter provides practical examples of integrating the `Maatify\SharedCommon` module into your application, focusing on DI container wiring and cross-module usage.

## 1. DI Container Wiring

The primary entry point for integrating the module is `SharedCommonBindings`.

```php
use DI\ContainerBuilder;
use Maatify\SharedCommon\Bootstrap\SharedCommonBindings;

$builder = new ContainerBuilder();

// Register the SharedCommon bindings
SharedCommonBindings::register($builder);

$container = $builder->build();
```

By default, the `SharedCommonBindings::register` method automatically resolves the system's current timezone using `date_default_timezone_get()` (falling back to `'UTC'`) and maps the `ClockInterface` to the `SystemClock` infrastructure implementation.

## 2. Using the Clock Interface Across Modules

Any service within a Maatify module that needs to check the current time should rely on the `ClockInterface` injected via dependency injection.

### Example: Token Generation

```php
use Maatify\SharedCommon\Contracts\ClockInterface;

class VerificationTokenGenerator
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function generateToken(): string
    {
        // Obtain the current DateTimeImmutable safely
        $now = $this->clock->now();

        // Calculate expiry safely
        $expiry = $now->modify('+15 minutes');

        // Logic to store the token and expiry...
        return bin2hex(random_bytes(16));
    }
}
```

This ensures time is generated consistently using the configured timezone and is completely decoupled from the system clock during testing.

## 3. Telemetry and Security Contexts

Modules that need to log context like an IP Address or User-Agent require the application to implement and bind the `TelemetryContextInterface` or `SecurityEventContextInterface`.

### Example: Audit Trail Service

```php
use Maatify\SharedCommon\Contracts\SecurityEventContextInterface;

class AuditTrailService
{
    public function __construct(private SecurityEventContextInterface $securityContext)
    {
    }

    public function logLoginAttempt(string $username, bool $success): void
    {
        // Request the context from the interface (agnostic of HTTP request details)
        $ip = $this->securityContext->getIpAddress();
        $userAgent = $this->securityContext->getUserAgent();

        // Logic to persist the audit log...
    }
}
```

By injecting `SecurityEventContextInterface`, the `AuditTrailService` can track events consistently without needing access to PSR-7 request headers or superglobals directly. This maintains a clean separation of concerns across the application.