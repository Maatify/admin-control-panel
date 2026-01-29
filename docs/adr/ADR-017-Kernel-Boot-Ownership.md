# ADR-017: Kernel Boot Ownership

## Status
Accepted

## Context
The previous `AdminKernel` implementation enforced a rigid bootstrap process, hardcoding the HTTP middleware stack and route loading via an internal `http.php` file. This prevented Host Applications from:
1. Customizing the runtime wiring (e.g., adding host-specific middleware).
2. Integrating the kernel into existing application lifecycles.
3. Controlling the bootstrap process declaratively.

This violated the principle that the Kernel should be a library, not a framework that dictates the entire application lifecycle.

## Decision
We have refactored `AdminKernel` to support **Host-Controlled Wiring**.

1.  **KernelOptions DTO**: A new `KernelOptions` DTO is introduced to encapsulate boot configuration, including `rootPath`, `loadEnv`, `builderHook`, and a new `bootstrap` callable.
2.  **bootWithOptions**: A new entry point `AdminKernel::bootWithOptions(KernelOptions $options)` allows full control over the boot process.
3.  **Default Fallback**: The existing `boot()` and `bootWithConfig()` methods remain as convenience wrappers, ensuring backward compatibility by defaulting to the internal `http.php` bootstrap.

## Consequences
- **Positive**: Host applications can now provide their own bootstrap logic (middleware, routes) while still leveraging the Kernel's container and domain logic.
- **Positive**: Backward compatibility is fully preserved for standalone usage.
- **Negative**: Host applications taking control of the bootstrap process are responsible for ensuring necessary kernel middleware (like `InputNormalizationMiddleware`) are correctly registered.

## Implementation
- `App\Kernel\KernelOptions`
- `App\Kernel\AdminKernel::bootWithOptions()`
