# Kernel Extraction Readiness — Final Gate Assessment

### 1. Executive Verdict
**⚠️ CONDITIONALLY ELIGIBLE**

### 2. Current Phase Identification
**Phase 14 (UI/UX) / Pre-Extraction Refactoring**

**Justification:**
- **Primary Phase:** The project is actively in **Phase 14** (UI/UX), as evidenced by `docs/PROJECT_CANONICAL_CONTEXT.md` ("Phase 14+ (UI/UX): ACTIVE") and recent commits involving `feat(ui-sessions)`, `feat(ui-admins)`, and `feat(ui-permissions)`.
- **Refactoring Status:** A significant parallel effort for **Kernel Extraction** is visibly underway and largely complete. Recent commits (`refactor(kernel): unlock AdminKernel boot with host-controlled runtime`, `refactor(kernel): extract runtime configuration`) demonstrate that the system has already moved its configuration and bootstrapping logic to a kernel-compatible DTO model.

### 3. Kernel Extraction Blockers Table

| Blocker | Category | Evidence (file / behavior) | Severity | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Missing Mandatory Compliance Documentation** | **Architectural / Audit** | `docs/PHASE_C4_SPEC_CONFORMANCE_REPORT.md` is missing.<br>`docs/architecture/authentication-architecture.md` is missing (found `docs/auth/auth-flow.md`).<br>`docs/architecture/failure-semantics.md` is missing (found `docs/auth/failure-semantics.md`). | **HARD** | **UNRESOLVED** |
| **MySQL Hardcoding (Driver)** | **Architectural** | `App\Infrastructure\Database\PDOFactory::create()` hardcodes `mysql:` DSN.<br>`Tests\Support\MySQLTestHelper` explicitly uses `SET FOREIGN_KEY_CHECKS` and `mysql:` DSN. | **HARD** (Architecture-Locked) | **UNRESOLVED** |
| **Test Suite Environment Coupling** | **Integration** | `Tests\Support\MySQLTestHelper` strictly enforces `APP_ENV=testing` and requires a live MySQL connection with specific credentials (non-injectable). | **SOFT** | **UNRESOLVED** |
| **Frontend Asset Coupling (CDN)** | **Host-Safety / UI** | `templates/layouts/base.twig` hardcodes external CDNs (Tailwind CSS, Google Fonts) within `head_assets`. | **SOFT** | **UNRESOLVED** |
| **Relative Path Fallbacks** | **Bootstrap** | `App\Bootstrap\Container.php` defaults to `__DIR__ . '/../../templates'` if path is not injected. While overridable, the default assumes a specific directory structure. | **SOFT** | **PARTIALLY ADDRESSED** |

### 4. Non-Blockers (Explicitly Confirmed Safe)
- **Runtime Configuration:** `App\Kernel\DTO\AdminRuntimeConfigDTO` successfully decouples the kernel from `$_ENV` and `Dotenv`. Confirmed in `App\Kernel\AdminKernel`.
- **Bootstrapping Control:** `AdminKernel::bootWithOptions()` allows full inversion of control for templates, assets, and routes.
- **Dependency Injection:** `App\Bootstrap\Container` strictly types all services and relies on the DTO for configuration, preventing hidden global state leaks.
- **Middleware Pipeline:** `AdminRoutes::register()` and `AdminKernel` allow the host to control the infrastructure middleware stack (`RequestId`, `Context`, `Telemetry`), preventing duplication or collision.
- **Database Abstraction (Application Layer):** The Domain and Application layers rely on `PDO`, not specific MySQL classes (except for the Factory). The `PDO` instance is correctly injected via DI.

### 5. Final Determination
**Can we extract the Kernel NOW?**

**YES, with constraints.**

The system is code-ready for physical extraction into a separate package/namespace. The primary architectural barrier—global state and environment coupling—has been successfully resolved via the `AdminRuntimeConfigDTO` and `AdminKernel::bootWithOptions` implementation.

**Constraints for Immediate Extraction:**
1.  **Strict MySQL Dependency:** The extracted kernel will **not** be database-agnostic. It will require a MySQL host environment. This is currently "Architecture-Locked" and must be accepted as a platform requirement.
2.  **Compliance Gap:** The missing C4 Spec Conformance Report represents a failure in the documentation/audit trail that must be rectified before a "Canonical" release, though it does not block the physical code extraction.
3.  **Host Connectivity:** The kernel requires internet access for frontend assets (CDNs) unless the host application explicitly overrides the `head_assets` block in Twig.
