# KERNEL READINESS ASSESSMENT (AS-IS)

## 1) Executive Summary

**Verdict:** CONDITIONAL ⚠️

The project exhibits a solid architectural foundation for a kernel library, characterized by strong isolation of configuration (DTOs), explicit middleware ownership, and clear boundaries between domain and infrastructure. However, it is currently tightly coupled to **MySQL**, effectively making it a "MySQL-only Admin Kernel" rather than a generic database-agnostic kernel.

**Key Findings:**
*   **Architecture & Isolation:** Excellent. The Kernel does not leak global state (timezone setting is removed) and relies entirely on injected DTOs (`AdminRuntimeConfigDTO`, `KernelOptions`).
*   **Database Lock-in:** The system is hardcoded to use MySQL via `PDOFactory` and uses MySQL-specific SQL syntax (`ON DUPLICATE KEY UPDATE`, `ENGINE=InnoDB`), preventing usage with other databases (PostgreSQL, SQLite).
*   **Frontend Dependencies:** The default UI layout relies on external CDNs (Tailwind, Google Fonts), which requires an internet connection unless overridden by the host.
*   **Middleware Control:** The host has full control over the middleware stack and route registration, which is ideal for embedding.

---

## 2) Boundary Breakers

### **A. Database Driver Hardcoding (High Severity)**
The kernel enforces the use of the MySQL driver. A host application using PostgreSQL or SQLite cannot embed this kernel without significant forking or refactoring.

*   **Evidence:** `app/Infrastructure/Database/PDOFactory.php` (Line 38)
    ```php
    $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";
    ```
*   **Evidence:** `database/schema.sql` (Throughout)
    *   Uses `ENGINE=InnoDB` and MySQL-specific syntax.
*   **Impact:** Prevents embedding in non-MySQL environments. The Kernel dictates the infrastructure rather than the Host.

### **B. SQL Syntax coupling (High Severity)**
Repositories contain raw SQL that is specific to MySQL dialects, breaking compatibility with other SQL standards.

*   **Evidence:** `app/Infrastructure/Repository/AdminPasswordRepository.php` (Lines 26-30)
    ```sql
    INSERT INTO ...
    ON DUPLICATE KEY UPDATE ...
    ```
*   **Impact:** Even if the PDO driver connection string was made configurable, the internal queries would fail on PostgreSQL/SQLite.

### **C. Testing Environment Coupling (Medium Severity)**
The provided test helpers are coupled to `getenv()` and strict environment variable naming, making it difficult for a host to reuse these helpers for their own integration tests involving the kernel.

*   **Evidence:** `tests/Support/MySQLTestHelper.php` (Lines 32-48)
    *   Directly calls `getenv('DB_HOST')`, `getenv('DB_NAME')`, etc.
    *   Throws exception if `APP_ENV !== 'testing'`.
*   **Impact:** Host applications cannot easily use the kernel's test utilities without replicating the exact environment variable structure and lifecycle of the kernel's dev environment.

---

## 3) Host Embedding Readiness

To embed this kernel **"as-is"**, a Host Application must:

1.  **Use MySQL:** The host must provision a MySQL database.
2.  **Provide Configuration DTO:** Instantiate `AdminRuntimeConfigDTO` manually (or mapping from their own config source) and pass it to `KernelOptions`.
3.  **Register Routes:** Explicitly call `AdminRoutes::register($app)` on their Slim App instance.
4.  **Install Dependencies:** Ensure `php-di/php-di`, `slim/slim`, `twig/twig`, and `symfony/polyfill-uuid` are available.

**Assumptions:**
*   The kernel assumes it can register its own DI definitions in the container without conflict (it uses a `builderHook` to allow host extensions, which is good).
*   The kernel assumes `date_default_timezone_set` is handled by the Host (correctly removed from `Container.php`).

---

## 4) Config & Env Isolation

**Verdict:** ✅ EXCELLENT

*   **Isolation:** The `AdminKernel` and `Container` **do not** read `$_ENV` or `getenv()` directly. They rely strictly on `AdminRuntimeConfigDTO` and `KernelOptions`.
*   **Injection:** All configuration (DB credentials, paths, secrets) is injected via the DTO.
*   **Bootstrapping:** The usage of `Dotenv` is confined to `public/index.php` (the example host) and `tests/bootstrap.php`, leaving the Kernel clean.

---

## 5) Filesystem / Paths / Assets

**Verdict:** ⚠️ CONDITIONAL

*   **Templates:** The `templates/` directory path is injectable via `KernelOptions::$templatesPath`. If not provided, it falls back to a relative path `__DIR__ . '/../../templates'`.
    *   **Risk:** `app/Modules/Email/Renderer/TwigEmailRenderer.php` uses `dirname(__DIR__, 4) . '/templates'` as a default. If the kernel is installed in `vendor/`, this relative path resolution must be verified to ensure it points to the package's templates, not the host's root.
*   **Assets (Frontend):**
    *   **Evidence:** `templates/layouts/base.twig` includes hardcoded CDN links for Tailwind CSS and Google Fonts.
    *   **Impact:** Air-gapped hosts or hosts with strict CSPs must override the `head_assets` block or the entire layout to provide local assets.
*   **Route Files:** The route file path is configurable via `KernelOptions::$routesFilePath`, defaulting to the internal `routes/web.php`.

---

## 6) Middleware & Routing Ownership

**Verdict:** ✅ KERNEL-GRADE

*   **Explicit Registration:** Routes are not auto-loaded. The host must call `AdminRoutes::register($app)`.
*   **Middleware Stack:** Infrastructure middleware (`RequestId`, `Context`, `Telemetry`) is applied within the `AdminRoutes` group or explicitly via options, preventing pollution of the global host middleware stack.
*   **Mounting:** The host can mount the admin routes under any path prefix (e.g., `/admin`) using Slim's `group()` functionality.

---

## 7) Test & Tooling Coupling

**Verdict:** ⚠️ MED

*   **Test Helpers:** `MySQLTestHelper` is rigid and strictly tied to the project's internal environment logic.
*   **Database Seeding:** `MySQLTestHelper::bootstrapDatabase` reads `database/schema.sql` from a relative path, which works for development but might be fragile if the helper is used from a `vendor` context.

---

## 8) Final Recommendations

To upgrade from **CONDITIONAL ⚠️** to **KERNEL-GRADE ✅**, the following changes are recommended:

1.  **Abstract Database Connection:**
    *   **Fix:** Refactor `PDOFactory` to accept a standard `PDO` instance or a connection string (DSN) passed via `AdminRuntimeConfigDTO`, rather than constructing the DSN internally with `mysql:` hardcoded.
    *   **Ref:** `app/Infrastructure/Database/PDOFactory.php`.

2.  **Abstract SQL Dialects:**
    *   **Fix:** Replace raw SQL queries in Repositories with a query builder or abstraction layer that supports at least MySQL and PostgreSQL (or SQLite for testing). Alternatively, explicitly document "MySQL Only" as a requirement.
    *   **Ref:** `app/Infrastructure/Repository/AdminPasswordRepository.php` (ON DUPLICATE KEY UPDATE).

3.  **Decouple Test Helpers:**
    *   **Fix:** Update `MySQLTestHelper` to accept configuration (PDO instance or credentials) via arguments instead of reading `getenv()` directly. This allows host apps to use the helper with their own test config.

4.  **Localize Default Assets:**
    *   **Fix:** Ship a build step or pre-built CSS/JS assets within `public/assets/` and reference them relatively in `base.twig`, removing the hard dependency on public CDNs for the default view.
