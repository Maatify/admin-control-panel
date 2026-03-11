# Chapter 2: Architecture

The `Maatify\SharedCommon` module follows an interface-segregation pattern, providing core domain contracts that dictate how time, telemetry, and security contexts are managed. It avoids tying the broader application to specific framework structures by enforcing decoupling.

## Layer Boundaries

The module is divided into three critical layers:

### 1. Contracts Layer (`Maatify\SharedCommon\Contracts`)

This is the central nervous system of `SharedCommon`. It defines the strict rules of engagement for all other modules.

*   **`ClockInterface`**: Enforces a strict contract for time tracking, returning immutable `DateTimeImmutable` objects and ensuring timezone predictability.
*   **`SecurityEventContextInterface`**: Exposes a unified contract for tracking security events across multiple requests, correlating logs via Request IDs and accessing network data.
*   **`TelemetryContextInterface`**: Provides a standard contract for retrieving HTTP-level context like IPs, User-Agents, and route names without tying modules to PSR-7, Symfony HttpFoundation, or plain `$_SERVER` superglobals.

### 2. Infrastructure Layer (`Maatify\SharedCommon\Infrastructure`)

This layer offers basic default implementations of the contracts defined in the `Contracts` layer. While some implementations are provided (like `SystemClock`), others are expected to be filled by the application.

*   **`SystemClock`**: A default implementation of `ClockInterface` that directly interacts with the system's local timezone.

### 3. Bootstrap Layer (`Maatify\SharedCommon\Bootstrap`)

This layer serves as the bridge between `SharedCommon` and any application's Dependency Injection container.

*   `SharedCommonBindings`: A static class that registers default bindings for `SharedCommon` contracts. It configures the container to automatically map `ClockInterface` to `SystemClock` by default, determining the local timezone automatically.

## Flow of Control

1.  **Application Logic** inside another module (e.g., `VerificationCodeGenerator`) calls `ClockInterface->now()`.
2.  The **DI Container** resolves `ClockInterface` to the bound `SystemClock` instance.
3.  The **Infrastructure Implementation** (`SystemClock`) generates a real `DateTimeImmutable` object based on the system's timezone.
4.  The **Application Logic** safely calculates TTLs and stores timestamps based on this immutable object, decoupled entirely from `date()`, `time()`, or the framework's internal request timing.