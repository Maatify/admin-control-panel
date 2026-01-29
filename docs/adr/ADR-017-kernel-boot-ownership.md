# ADR-017: Kernel Boot Ownership and Runtime Extensibility

## Status
Accepted

## Context
The previous implementation of `AdminKernel::boot()` and `AdminKernel::bootWithConfig()` was rigid in its runtime wiring. It hardcoded:
1. The HTTP middleware bootstrapping logic (via `app/Bootstrap/http.php`).
2. The route registration logic (implicitly loaded by `http.php` via `routes/web.php`).
3. The execution order of these components.

This prevented Host Applications from:
- Injecting custom middleware stacks.
- Overriding or extending route loading strategies.
- Integrating the kernel into existing application runtimes without forking or hacking internal files.

## Decision
We have unlocked the AdminKernel boot process by introducing a `KernelOptions` DTO and a new entry point, allowing full Host control over runtime wiring while preserving safe defaults.

1. **`App\Kernel\KernelOptions` DTO**:
   - Encapsulates boot configuration.
   - Properties:
     - `?string $rootPath`: Root directory for env loading.
     - `bool $loadEnv`: Whether to load `.env`.
     - `?callable $builderHook`: Hook for DI container extension.
     - `?callable $bootstrap`: Custom callable for middleware/error handler setup.
     - `?callable $routes`: Custom callable for route registration.

2. **Decoupled Bootstrapping Files**:
   - `app/Bootstrap/http.php` was modified to **only** set up HTTP middleware (BodyParsing, ErrorMiddleware) and **no longer** loads routes.
   - Route loading is now a separate, explicit step in the kernel boot process.

3. **`App\Kernel\AdminKernel::bootWithOptions`**:
   - The new canonical boot method that accepts `KernelOptions`.
   - It instantiates the Container and App.
   - It invokes `$options->bootstrap` (defaulting to `app/Bootstrap/http.php`).
   - It invokes `$options->routes` (defaulting to `routes/web.php`).

4. **Backward Compatibility**:
   - `AdminKernel::boot()` and `AdminKernel::bootWithConfig()` remain available.
   - They internally construct `KernelOptions` with the default callables, ensuring that existing calls (and standalone usage) continue to function exactly as before.

## Consequences
- **Positive:** Host Applications can now override the bootstrap logic (e.g., adding global middleware) or route loading (e.g., mounting routes differently) by passing custom callables in `KernelOptions`.
- **Positive:** The separation of concerns between "HTTP Middleware Setup" and "Route Registration" is now strict and explicit.
- **Positive:** Default behavior for standalone usage is preserved.
- **Compliance:** PHPStan level max is maintained.
