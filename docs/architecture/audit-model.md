# Audit Model Architecture

## Core Design Principle: Extensibility

The `audit_logs` table uses a **Actor/Target** model to track system mutations.
A critical design decision in this model is the definition of `target_type`.

### 1. Explicit Statement

*   **`target_type` is intentionally a STRING (VARCHAR).**
*   It is **NOT** an enum.
*   It is **NOT** a foreign key constraint.

### 2. Rationale

The primary goal is **Domain Extensibility without Migrations**.

*   **Future-Proofing:** The system will expand to include new domains (e.g., Products, Orders, SystemConfigs) without requiring database schema changes or migrations for the audit log table.
*   **Decoupling:** The Audit domain should not be tightly coupled to the implementation details of every other domain. It is a passive observer.
*   **Flexibility:** Allows logging of cross-domain actions, self-modifying actions, or actions on non-database entities (e.g., "system_cache") without strict relational constraints.

### 3. Examples (Non-Binding)

The following are valid `target_type` values. These are examples of convention, not enforcement:

*   `admin`
*   `admin_email`
*   `role`
*   `permission`
*   `product`
*   `self` (e.g., Admin updating their own profile)
*   `system`

### 4. Query Patterns

The structure supports key operational queries:

*   **Actor-based Search:** "Show me everything Admin #123 did."
    *   `SELECT * FROM audit_logs WHERE actor_admin_id = 123`
*   **Target-based Search:** "Show me the history of changes to Product #55."
    *   `SELECT * FROM audit_logs WHERE target_type = 'product' AND target_id = 55`
*   **Self-Action Detection:** "Did Admin #123 modify themselves?"
    *   `SELECT * FROM audit_logs WHERE actor_admin_id = 123 AND target_type = 'admin' AND target_id = 123`

### 5. Explicit Non-Goals

*   **No Validation Enforcement:** The database will not reject unknown `target_type` strings.
*   **No Enum Introduction:** PHP Enums or MySQL ENUM columns are explicitly avoided to prevent deployment bottlenecks.
*   **No FK Constraints:** We do not enforce foreign keys on `target_id` because targets may come from different tables or external systems.
*   **No Behavior Logic:** The audit layer is for recording history, not for driving business logic.
