# Kernel Boundary Classification Report

## 1. Executive Summary
The Admin Control Panel operates under a **Module-Centric, Override-First** architectural model. The Kernel acts as a "Default Implementation Module," providing a fully functional, secure, and compliant admin interface out-of-the-box. The Host Application maintains authority by "mounting" the Kernel's routes and injecting configuration, while retaining the capability to override specific behaviors (Controllers, Templates, Services) via Dependency Injection and Template Stacking. The system effectively treats the Kernel as a vendor library that provides the standard "Admin Domain," while the Host owns the runtime environment and business-specific extensions.

## 2. Ownership Classification Table

| Component Area | Owner (Kernel / Host) | Override-Safe | Rationale |
| :--- | :--- | :--- | :--- |
| **Domain Logic** | **Kernel (Strict)** | ❌ No | Core business rules (Auth, RBAC, Security) are canonical and frozen. Host must not alter these invariants. |
| **App Services** | **Kernel (Default)** | ✅ Yes | Wired via DI Interface. Host can swap implementation (e.g., custom `NotificationDispatcher`) via `builderHook`. |
| **Controllers** | **Kernel (Default)** | ✅ Yes | Resolved via DI container. Host can rebind controller classes to subclasses or custom implementations. |
| **Routes** | **Kernel (Default)** | ⚠️ Partial | Host mounts `AdminRoutes::register()`. Host can add routes but strictly strictly removing standard routes is high-friction. |
| **Templates** | **Kernel (Default)** | ✅ Yes | **Template Stacking** is enforced. Host templates (`hostTemplatePath`) take precedence over Kernel templates (`templates/`). |
| **Assets** | **Kernel (Default)** | ✅ Yes | Kernel provides default assets. Host can override via `UiConfigDTO` or serve custom assets at the same paths. |
| **Config Defaults** | **Kernel** | ❌ No | Kernel defines the schema (`AdminConfigDTO`). Host provides values. Defaults are strict (Fail-Closed). |
| **Bootstrap** | **Shared** | N/A | Host owns `index.php` and `bootstrap.php` (Entry), Kernel owns `AdminKernel` and `Container` (Mechanism). |

## 3. Kernel Default Surface
The Kernel MUST provide the following as the **Standard Definition of "Admin Panel"**:

1.  **Canonical Security Architecture**:
    *   Authentication Services (`AdminAuthenticationService`, `SessionGuard`).
    *   Authorization Logic (`RBAC`, `ScopeGuard`).
    *   Crypto Pipeline (`KeyRotation`, `EncryptedPayload`).
    *   Audit & Logging Writers (`AuditTrail`, `AuthoritativeAudit`).

2.  **Standard UI Implementation**:
    *   All Twig templates in `templates/` (Layouts, Pages, Components).
    *   Default Asset Bundle (CSS/JS).
    *   `Ui*Controller` classes for standard resource management.

3.  **Infrastructure Wiring**:
    *   `Container` factory with strict type bindings.
    *   `AdminRoutes` registry with Canonical Middleware Pipeline.
    *   `PDO` repository implementations for Admin Domain entities.

## 4. Host-Only Responsibilities
The Host Application MUST retain exclusive ownership of:

1.  **Runtime Configuration**:
    *   Environment variables (`.env`).
    *   Secrets management (Crypto Keys, Database Credentials).
    *   `AdminRuntimeConfigDTO` population.

2.  **Entry Point & Bootstrapping**:
    *   `public/index.php` (Web Entry).
    *   `scripts/` (CLI Entry).
    *   Definition of the `builderHook` for DI extension.

3.  **Infrastructure Provisioning**:
    *   Database Server (MySQL).
    *   SMTP Server.
    *   Filesystem permissions.

## 5. Boundary Violations
The following components currently violate the strict Module-Centric model:

1.  **Database Driver Coupling**:
    *   **Violation**: `App\Infrastructure\Database\PDOFactory` hardcodes `mysql:` DSN.
    *   **Impact**: Prevents Host from using compatible drivers (e.g., MariaDB specific modes, or potentially SQLite for testing) without replacing the entire Factory.

2.  **Frontend Asset Coupling**:
    *   **Violation**: `templates/layouts/base.twig` contains hardcoded CDN links for Tailwind CSS and Google Fonts.
    *   **Impact**: Violates "Host-Safety" for air-gapped or compliance-heavy Hosts that cannot rely on public CDNs. Host is forced to override the entire layout to fix this.

3.  **Testing Environment Leak**:
    *   **Violation**: `Tests\Support\MySQLTestHelper` is tightly coupled to specific environment variable names and the Kernel's internal schema path.
    *   **Impact**: Host cannot easily reuse the Kernel's test suite pattern for its own extensions without replicating the environment setup exactly.
