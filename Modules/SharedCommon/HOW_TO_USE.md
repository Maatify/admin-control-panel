# How to Use

This guide provides practical instructions on how to integrate the `Maatify\SharedCommon` module into your application, ensuring consistent time tracking, telemetry, and security contexts.

## 1. Container Bindings

The `SharedCommon` module provides a `SharedCommonBindings` class that can be used with a PHP-DI `ContainerBuilder` to map the provided default infrastructure (like `SystemClock`) to the interface.

```php
use DI\ContainerBuilder;
use Maatify\SharedCommon\Bootstrap\SharedCommonBindings;

$builder = new ContainerBuilder();

// Register the SharedCommon bindings
SharedCommonBindings::register($builder);

$container = $builder->build();
```

By default, the `SharedCommonBindings::register` method resolves the system's current timezone using `date_default_timezone_get()` (falling back to `'UTC'` if not set) and wires up a `SystemClock` instance for `ClockInterface`.

## 2. Using the Clock Interface

Once the bindings are registered, any service that needs to check the current time should rely on the `ClockInterface` instead of PHP's global `date()`, `time()`, or `new \DateTime()`.

```php
use Maatify\SharedCommon\Contracts\ClockInterface;

class VerificationService
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function generateTokenExpiry(): \DateTimeImmutable
    {
        // Obtain the current DateTimeImmutable safely
        $now = $this->clock->now();

        // Add 15 minutes to the current time
        return $now->modify('+15 minutes');
    }
}
```

This ensures that time is predictable and strictly managed via a `DateTimeImmutable` instance within the designated timezone.

## 3. Extending the Contracts

The `SharedCommon` module defines several contracts that are meant to be implemented by application-specific infrastructure. For example, the `TelemetryContextInterface` and `SecurityEventContextInterface`.

You should map these interfaces to concrete implementations within your specific framework's request cycle.

### Example: Implementing `TelemetryContextInterface`

```php
use Maatify\SharedCommon\Contracts\TelemetryContextInterface;
use Psr\Http\Message\ServerRequestInterface;

class HttpTelemetryContext implements TelemetryContextInterface
{
    public function __construct(private ServerRequestInterface $request)
    {
    }

    public function getRequestId(): ?string
    {
        return $this->request->getHeaderLine('X-Request-Id') ?: uniqid('req_', true);
    }

    public function getRouteName(): ?string
    {
        // Example logic to fetch the route name from Slim or another router
        return $this->request->getAttribute('route')?->getName();
    }

    public function getIpAddress(): ?string
    {
        $serverParams = $this->request->getServerParams();
        return $serverParams['REMOTE_ADDR'] ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->request->getHeaderLine('User-Agent');
    }
}
```

Then, explicitly bind this context in your application's Dependency Injection logic to fulfill the contract for any other module that needs telemetry data.

```php
// Binding a context to the telemetry interface
$builder->addDefinitions([
    TelemetryContextInterface::class => \DI\autowire(HttpTelemetryContext::class),
]);
```

By doing this, any module inside Maatify can reliably pull an IP Address or User Agent without knowing how the HTTP request is actually processed.