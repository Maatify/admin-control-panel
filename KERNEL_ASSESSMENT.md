# Kernel Readiness Assessment

**Executive Verdict: CONDITIONAL**

## Explicit Boundary Violations
1.  **Database Driver Coupling**: `App\Infrastructure\Database\PDOFactory` hardcodes the `mysql:` DSN prefix:
    ```php
    $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";
    ```
    This prevents the Host from using any other database driver (PostgreSQL, SQLite) even if the SQL were compatible.

2.  **SQL Dialect Coupling**: Multiple repositories (e.g., `AdminPasswordRepository`, `PdoAdminNotificationPreferenceRepository`) utilize MySQL-specific syntax:
    ```sql
    ON DUPLICATE KEY UPDATE ...
    ```
    This syntax is incompatible with SQLite (which uses `ON CONFLICT`) and PostgreSQL (which uses `ON CONFLICT`). This strictly couples the Kernel to a MySQL backend.

3.  **UI/Asset Dependencies**: The core layout template `templates/layouts/base.twig` contains hardcoded external CDN references:
    ```html
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?..." rel="stylesheet">
    ```
    This forces the Host Application to have public internet access, violating air-gapped/offline usage requirements and imposing external uptime dependencies.

## Risky Couplings
1.  **Unused Hard Dependency**: `composer.json` requires `ext-redis`, but the default `Container` configuration wires `StepUpGrantRepositoryInterface` to `PdoStepUpGrantRepository` (MySQL). The Host is forced to provision the Redis extension even when using the default SQL storage.
2.  **Filesystem Assumptions**: `App\Bootstrap\Container` defaults the template path to:
    ```php
    $kernelPath = $templatesPath ?? (__DIR__ . '/../../templates');
    ```
    This assumes a specific directory depth relative to the bootstrap file, which may break if the library is repackaged or the directory structure is flattened by optimization tools.

## Hidden Assumptions the Host must satisfy
1.  **MySQL Environment**: The Host must provide a MySQL database. Usage of SQLite for integration testing or PostgreSQL for production is impossible without code modification.
2.  **Internet Access**: The Host environment must allow outbound HTTPS traffic to `cdn.jsdelivr.net` and `fonts.googleapis.com` for the UI to render correctly.

## Non-negotiable blockers
1.  **Hardcoded MySQL Driver & Syntax**: The Kernel cannot be embedded in a Host using PostgreSQL or SQLite.
2.  **External Asset Dependencies**: The Kernel cannot be used in strict security environments (e.g., banking, healthcare) where external CDNs are blocked.

## Minimal changes required to reach Kernel-Grade
1.  **Refactor PDOFactory**: Modify `PDOFactory` to accept a full DSN string or allow the Host to inject a configured `PDO` instance directly, rather than constructing it from credentials with a hardcoded prefix.
2.  **Abstract SQL Dialects**: Replace `ON DUPLICATE KEY UPDATE` with an ANSI SQL equivalent or an abstraction layer that handles dialect differences (e.g., `INSERT OR IGNORE` + `UPDATE`, or `ON CONFLICT` for PG/SQLite).
3.  **Configurable Assets**: Move Tailwind and Font URLs to `UiConfigDTO` (defaulting to CDNs) so the Host can override them with local paths or internal mirrors.
4.  **Relax Redis Requirement**: Move `ext-redis` to `composer.json`'s `suggest` section since the default implementation is PDO-based.
