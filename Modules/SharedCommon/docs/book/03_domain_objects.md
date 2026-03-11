# Chapter 3: Domain Objects

The `Maatify\SharedCommon` module defines three core contracts (interfaces) that act as the foundational Domain Objects for any Maatify module. These objects encapsulate critical contextual data related to time, telemetry, and security, decoupling modules from the underlying PHP superglobals or framework-specific HTTP requests.

## 1. `ClockInterface`

The most crucial contract is `ClockInterface`, abstracting the concept of "now". It enforces that all modules interact with time through predictable `DateTimeImmutable` objects.

```php
declare(strict_types=1);

namespace Maatify\SharedCommon\Contracts;

use DateTimeImmutable;
use DateTimeZone;

interface ClockInterface
{
    public function now(): DateTimeImmutable;

    public function getTimezone(): DateTimeZone;
}
```

**Key Points:**
- `now()`: Always returns an immutable timestamp. This prevents accidental mutations of time state across different services.
- `getTimezone()`: Provides the current timezone configured for the system, ensuring consistent formatting and persistence of timestamps.

## 2. `SecurityEventContextInterface`

This interface defines a unified contract for tracking security-relevant events across the application lifecycle. It provides contextual data for logs, audits, and security assertions.

```php
declare(strict_types=1);

namespace Maatify\SharedCommon\Contracts;

interface SecurityEventContextInterface
{
    public function getRequestId(): ?string;
    public function getIpAddress(): ?string;
    public function getUserAgent(): ?string;
    public function getRouteName(): ?string;
}
```

**Key Points:**
- `getRequestId()`: A unique identifier that correlates all logs/events generated during the same request lifecycle (e.g., `X-Request-Id`).
- `getIpAddress()`: The client's IP address (IPv4 or IPv6), safely extracted from headers or superglobals.
- `getUserAgent()`: The client's User-Agent string.
- `getRouteName()`: The logical route or permission name associated with the request (e.g., `admin.login`).

## 3. `TelemetryContextInterface`

Similar to `SecurityEventContextInterface`, this contract defines a request-scoped data source for general telemetry and monitoring. It acts as a lightweight abstraction over HTTP requests, allowing any module to pull context without depending on PSR-7 or Symfony HttpFoundation.

```php
declare(strict_types=1);

namespace Maatify\SharedCommon\Contracts;

interface TelemetryContextInterface
{
    public function getRequestId(): ?string;
    public function getRouteName(): ?string;
    public function getIpAddress(): ?string;
    public function getUserAgent(): ?string;
}
```

**Key Points:**
- Provides a consistent interface for extracting HTTP-level context (IPs, User-Agents, Request IDs, Route Names) across disparate modules.
- Often, the concrete implementation for this interface is provided by the application's HTTP layer (e.g., `RequestContext` in `AdminKernel`).