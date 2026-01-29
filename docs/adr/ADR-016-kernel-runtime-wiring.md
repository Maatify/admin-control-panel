# ADR-016: Kernel Runtime Wiring and Environment Loading

## Status
Accepted

## Context
The previous implementation of the `AdminKernel` and `Container` assumed that the `.env` file was located two directories above the bootstrap file (`__DIR__ . '/../../'`). This assumption held true for standalone installations but failed when the kernel was installed as a dependency in a host application (e.g., inside `vendor/maatify/admin-control-panel/`), where the root directory differs.

To support the transition of `admin-control-panel` to a reusable kernel package, we must allow the Host Application to define:
1. The root path for environment loading.
2. Whether the kernel should load the `.env` file at all (or if the Host manages environment variables).

## Decision
We have enforced strict configuration for environment loading in the `Container`, removing all implicit path assumptions.

1. **`App\Bootstrap\Container::create`** now enforces strict constraints:
   - `?string $rootPath`: Custom path to the directory containing `.env`.
   - `bool $loadEnv`: Whether to load the `.env` file using `vlucas/phpdotenv`. Defaults to `true`.
   - **Constraint:** If `$loadEnv` is `true`, `$rootPath` **MUST** be provided. If it is `null`, the Container throws a `RuntimeException`. Implicit fallbacks to vendor-relative paths are forbidden.

2. **`App\Kernel\AdminKernel`** methods:
   - **`bootWithConfig`** (New):
     - Signature: `bootWithConfig(?string $rootPath = null, bool $loadEnv = true, ?callable $builderHook = null): App`
     - This is the primary entry point for Host Applications. It delegates directly to `Container::create`.
     - Host Apps must provide a valid `$rootPath` if they want the Kernel to load `.env`.
   - **`boot`** (Legacy/Standalone):
     - Explicitly passes the local package root (`__DIR__ . '/../../'`) to `Container::create`.
     - This preserves backward compatibility for standalone usage (e.g., development of the package itself) but restricts the "root assumption" to this specific convenience method, keeping the core Container logic pure.

## Consequences
- **Positive:** The kernel strictly validates its configuration, preventing silent failures or accidental loading of `.env` files from incorrect locations.
- **Positive:** Host Applications have full control over environment loading.
- **Positive:** Standalone usage remains functional via the legacy `boot` method.
- **Compliance:** PHPStan level max is maintained.
