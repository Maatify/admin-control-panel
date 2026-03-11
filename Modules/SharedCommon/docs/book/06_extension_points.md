# Chapter 6: Extension Points

The `Maatify\SharedCommon` module is designed to be highly extensible without modifying the core domain services. This allows applications to provide specific implementations for contextual data collection, ensuring that logging and auditing logic can remain decoupled from HTTP frameworks.

## 1. Application-Specific Telemetry Contexts

The `TelemetryContextInterface` acts as a request-scoped data source for general telemetry and monitoring. It acts as a lightweight abstraction over HTTP requests, allowing any module to pull context without depending on PSR-7 or Symfony HttpFoundation.

You should map this interface to concrete implementations within your specific framework's request cycle.

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

## 2. Application-Specific Security Contexts

Similar to the `TelemetryContextInterface`, the `SecurityEventContextInterface` defines a unified contract for tracking security-relevant events across the application lifecycle.

### Example: Implementing `SecurityEventContextInterface`

```php
use Maatify\SharedCommon\Contracts\SecurityEventContextInterface;
use Psr\Http\Message\ServerRequestInterface;

class HttpSecurityEventContext implements SecurityEventContextInterface
{
    public function __construct(private ServerRequestInterface $request)
    {
    }

    public function getRequestId(): ?string
    {
        return $this->request->getHeaderLine('X-Request-Id');
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

    public function getRouteName(): ?string
    {
        return $this->request->getAttribute('route')?->getName();
    }
}
```

Bind this custom context in your DI container:

```php
$builder->addDefinitions([
    SecurityEventContextInterface::class => \DI\autowire(HttpSecurityEventContext::class),
]);
```

Modules like the `AuditTrail` or `Verification` modules can now easily request security data by depending solely on the interface, without direct knowledge of the application's HTTP implementation details.