# Admin Kernel Extension & Locking Audit Report

## 1) ✅ READY

*   **`app/Infrastructure/Repository/` (excluding `AdminRepository`)**
    *   Standard PDO implementations (e.g., `PdoRoleRepository`, `PdoSessionListReader`) are isolated and kernel-compatible.
*   **`app/Modules/` (Crypto, Validation, Email, InputNormalization)**
    *   Self-contained domains with clear interfaces and configuration DTOs; safe for direct kernel inclusion.
*   **`app/Http/Controllers/Api/`**
    *   Canonical API endpoints (e.g., `SessionQueryController`, `AdminQueryController`) follow strict contracts and require no extension.
*   **`app/Domain/DTO/`**
    *   Pure data structures (e.g., `AdminConfigDTO`, `EmailTransportConfigDTO`) are stable contracts.
*   **`app/Modules/Email/Transport/SmtpEmailTransport.php`**
    *   Standard implementation of `EmailTransportInterface`; swappable via container if needed.

## 2) 🧩 NEEDS EXTENSION HOOK

*   **`app/Bootstrap/Container.php`**
    *   **Reason:** Currently a monolithic static factory (`create()`) that hardcodes all bindings. Host projects cannot inject custom services, repositories, or configuration without modifying this file.
    *   **Hook Type:** Container Builder Hook (e.g., `Container::create(callable $builderHook)`) or a Service Provider pattern to allow host-level overrides.
*   **`routes/web.php`**
    *   **Reason:** Defines a single closure returning a `RouteCollectorProxy`. Host projects need to mount these routes under a prefix (e.g., `/admin`) or merge them with their own routing table.
    *   **Hook Type:** Routing Provider / Mountable Callable (e.g., `AdminRoutes::register($app, $prefix)`).
*   **`templates/layouts/base.twig`**
    *   **Reason:** The Navigation Menu (`nav_items`) is hardcoded within the template. Host projects cannot add their own menu items. Frontend assets (`/assets/css/style.css`) are hardcoded to the root.
    *   **Hook Type:** Template Variable Injection (Global) for Menu Items and a configuration for Asset Base URL.
*   **`app/Infrastructure/Repository/AdminRepository.php`**
    *   **Reason:** While the implementation is solid, host projects may need to extend the `Admin` entity or integrate with existing user tables. The current class is concrete and final-ish.
    *   **Hook Type:** Container Binding Override (Interface-based binding is present in usage but the class itself is concrete in some definitions).
*   **`public/index.php` (Conceptually)**
    *   **Reason:** The entry point. A kernel should provide a bootstrapper, but the host application owns the actual entry point.
    *   **Hook Type:** A `Kernel::boot()` method that the host's `index.php` calls.

## 3) 🔒 MUST BE LOCKED

*   **`app/Domain/Service/AdminAuthenticationService.php`**
    *   **Justification:** Core security logic for login, password enforcement, and session creation. Modifying this risks bypassing auth guarantees.
*   **`app/Domain/Service/AuthorizationService.php`**
    *   **Justification:** Enforces the canonical non-hierarchical RBAC model. Overriding this breaks the permission contract.
*   **`app/Http/Middleware/AuthorizationGuardMiddleware.php`**
    *   **Justification:** The gatekeeper for all protected routes. Relies on strict Route Name -> Permission mapping.
*   **`app/Http/Middleware/SessionGuardMiddleware.php`**
    *   **Justification:** Manages session validity, timeouts, and identity resolution. Critical for security.
*   **`app/Domain/Contracts/*.php`**
    *   **Justification:** These define the canonical boundaries. Changing interfaces breaks the kernel promise.
*   **`docs/PROJECT_CANONICAL_CONTEXT.md` & `docs/API_PHASE1.md`**
    *   **Justification:** The authoritative source of truth for the system's behavior.

## Overall Readiness Assessment

The repository is **structurally sound** and follows a strict DDD/Clean Architecture, making it a strong candidate for a kernel. The core domains (Auth, Roles, Crypto) are well-isolated and ready for locking.

However, the **Bootstrapping and UI layers are currently monolithic**. The `Container.php` file prevents any external dependency injection, and the `base.twig` layout hardcodes navigation, rendering the panel unusable for any host app that needs to add its own pages. These are significant hurdles to kernelization that must be resolved via extension hooks.

## Blockers & Ambiguities

1.  **Asset Management:** The templates reference `/assets/css/style.css` and local images. In a kernel/vendor package scenario, these assets are not automatically available in the host's `public/` directory. A mechanism to publish these assets (e.g., a CLI command) or serve them is missing.
2.  **Monolithic Container:** `app/Bootstrap/Container.php` is the single point of failure for extensibility. It must be refactored to accept external configuration/bindings.
3.  **Hardcoded Navigation:** `templates/layouts/base.twig` defines the menu structure inline. This prevents host applications from registering their own modules in the sidebar.
