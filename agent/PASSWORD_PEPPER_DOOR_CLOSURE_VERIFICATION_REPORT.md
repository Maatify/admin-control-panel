# Password Pepper Door Closure Verification Report

**Status:** PASS
**Date:** 2025-02-24
**Project:** admin-control-panel

This report verifies that the password pepper architecture has been successfully migrated to a strict "Pepper Ring" model (Option B). The database reset allows for a clean break, ensuring ZERO legacy fallback paths exist.

---

## 1. Boot Fail-Closed Proof

The application strictly enforces the presence and validity of the Pepper Ring configuration at boot time.

*   **Environment Loading & Validation:**
    *   **File:** `app/Bootstrap/Container.php`
    *   **Method:** `Container::create` (Config DTO creation block)
    *   **Proof:**
        *   **Missing `PASSWORD_PEPPERS`:** Throws `Exception('PASSWORD_PEPPERS is required and cannot be empty.')`.
        *   **Invalid JSON/Empty:** Throws `Exception('PASSWORD_PEPPERS must be a non-empty JSON object map')`.
        *   **Short Secret:** Throws `Exception("Pepper secret for ID '$id' is too short (min 32 chars).")`.
        *   **Missing `PASSWORD_ACTIVE_PEPPER_ID`:** Throws `Exception('PASSWORD_ACTIVE_PEPPER_ID is required.')`.
        *   **Active ID Not in Ring:** Throws `Exception("PASSWORD_ACTIVE_PEPPER_ID '...' not found in PASSWORD_PEPPERS.")`.

## 2. Schema Closure Proof

The database schema enforces the association of every password hash with a specific pepper ID.

*   **Schema Definition:**
    *   **File:** `database/schema.sql`
    *   **Table:** `admin_passwords`
    *   **DDL Snippet:**
        ```sql
        CREATE TABLE admin_passwords (
            admin_id INT PRIMARY KEY,
            password_hash VARCHAR(255) NOT NULL,
            pepper_id VARCHAR(16) NOT NULL,  -- NOT NULL constraint enforces pepper association
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_ap_admin_id FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ```
*   **Insert/Update Path:**
    *   **File:** `app/Infrastructure/Repository/AdminPasswordRepository.php`
    *   **Method:** `savePassword`
    *   **Proof:** The SQL `INSERT` statement explicitly requires `pepper_id`.
        ```php
        INSERT INTO admin_passwords (admin_id, password_hash, pepper_id) VALUES (?, ?, ?)
        ```

## 3. Single Deterministic Verification Path

Verification is now a single-pass operation using the stored pepper ID. All "try-and-see" legacy logic has been removed.

*   **Canonical Service:** `app/Domain/Service/PasswordService.php`
*   **Method:** `verify(string $plain, string $hash, string $pepperId): bool`
*   **Proof of Strictness:**
    *   **Lookup:** `$pepper = $this->peppers[$pepperId];`
    *   **Fail-Closed:** If `$pepperId` is not in the configured ring, it returns `false` immediately.
    *   **No Fallback:** There are no loops, no `try...catch` blocks for legacy peppers, and no calls to `password_verify` without a pepper.

## 4. Rotation Readiness + Upgrade-on-Login

The system automatically upgrades passwords to the currently active pepper upon successful login.

*   **Logic Location:** `app/Domain/Service/AdminAuthenticationService.php`
*   **Method:** `login`
*   **Trigger:**
    ```php
    if ($this->passwordService->needsRehash($record->pepperId)) { ... }
    ```
    (`needsRehash` returns true if stored `pepperId` !== `activePepperId`).
*   **Atomicity:**
    *   The upgrade logic is wrapped within `$this->pdo->beginTransaction();` ... `$this->pdo->commit();`.
    *   It occurs in the same transaction as the session creation.
    *   Any failure triggers `$this->pdo->rollBack();`.

## 5. Legacy Surface Area Elimination (Inventory)

A repository-wide search confirms the removal of legacy configuration keys and logic.

*   **Search: `PASSWORD_PEPPER` (Single Var)**
    *   **Hits:** 0 in code logic.
    *   **Notes:** References remain only in documentation (`agent/*.md`, `.env.example` comments explaining the *change*). No functional code uses this.

*   **Search: `PASSWORD_PEPPER_OLD`**
    *   **Hits:** 0 in code logic.
    *   **Notes:** References remain only in documentation (`agent/*.md`).

*   **Search: "no pepper" / legacy verify patterns**
    *   **Hits:** 0 in code logic.
    *   **Notes:** Documentation only.

*   **Search: `getPasswordHash` (Legacy Helper)**
    *   **Hits:** 0.

**Verdict:** 0 functional legacy hits.

## 6. Fresh Install Readiness

The bootstrap script uses the new API, ensuring the very first admin created is fully compliant.

*   **File:** `scripts/bootstrap_admin.php`
*   **Proof:**
    *   Retrieves `PasswordService` from the container.
    *   Calls `$hashResult = $passwordService->hash($password);`.
    *   Saves using `$passRepo->savePassword($adminId, $hashResult['hash'], $hashResult['pepper_id']);`.

## 7. Final “Door Closed 100%” Verdict

| Requirement | Status | Notes |
| :--- | :--- | :--- |
| 1. Boot Fail-Closed | **PASS** | Container throws exceptions for any config violation. |
| 2. Schema Closure | **PASS** | `pepper_id` is `NOT NULL` in `admin_passwords`. |
| 3. Deterministic Verify | **PASS** | `PasswordService::verify` is strict and single-path. |
| 4. Rotation/Upgrade | **PASS** | Atomic upgrade-on-login implemented in `AdminAuthenticationService`. |
| 5. Legacy Elimination | **PASS** | Zero functional references to legacy keys. |
| 6. Fresh Install | **PASS** | Bootstrap script uses new `pepper_id` aware API. |

**Conclusion:**
The password pepper door is **CLOSED 100%**. The system is now operating on a strict Pepper Ring architecture with no legacy fallback paths. The database reset ensures all data will be compliant from the start.
