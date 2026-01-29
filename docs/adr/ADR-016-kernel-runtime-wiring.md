# ADR-016: Kernel Runtime Wiring and Environment Loading

## Status
Accepted

## Context
The previous implementation of the `AdminKernel` and `Container` assumed that the `.env` file was located two directories above the bootstrap file (`__DIR__ . '/../../'`). This assumption held true for standalone installations but failed when the kernel was installed as a dependency in a host application (e.g., inside `vendor/maatify/admin-control-panel/`), where the root directory differs.

To support the transition of `admin-control-panel` to a reusable kernel package, we must allow the Host Application to define:
1. The root path for environment loading.
2. Whether the kernel should load the `.env` file at all (or if the Host manages environment variables).

## Decision
We have introduced flexible configuration for environment loading in the Kernel boot process:

1. **`App\Bootstrap\Container::create`** now accepts optional parameters:
   - `?string $rootPath`: Custom path to the directory containing `.env`. Defaults to `__DIR__ . '/../../'` (package root) if null.
   - `bool $loadEnv`: Whether to load the `.env` file using `vlucas/phpdotenv`. Defaults to `true`.

2. **`App\Kernel\AdminKernel`** exposes a new method **`bootWithConfig`**:
   - Signature: `bootWithConfig(?string $rootPath = null, bool $loadEnv = true, ?callable $builderHook = null): App`
   - This method allows the Host Application to explicitly configure the runtime environment.
   - The existing `boot` method remains unchanged for backward compatibility and standalone usage defaults.

## Consequences
- **Positive:** The kernel can now be safely used as a library in a Host Application without hardcoded path assumptions.
- **Positive:** Host Applications can manage environment variables independently and disable the kernel's internal `.env` loading.
- **Negative:** There is slight code duplication between `boot` and `bootWithConfig` to strictly maintain the immutability of the original `boot` implementation logic.
- **Compliance:** PHPStan level max is maintained, and strict typing is enforced.
