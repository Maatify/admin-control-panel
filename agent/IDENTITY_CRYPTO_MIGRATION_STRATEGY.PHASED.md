# Identity Crypto Migration Strategy (Executable, Phased)

**Role:** Security / Crypto Architect  
**Scope:** Admin Identity (Emails + Blind Indexes)  
**Mode:** Strict / Read-Only / Design-Only  
**Target:** `admin-control-panel`

---

## 1. Executive Summary

**Objective:** Migrate Admin Identity cryptography from a legacy, static-key implementation to the canonical `CryptoProvider` and `KeyRotationService` architecture without downtime or data loss.

**Why this is critical:**
1.  **Rotation Lock-in:** Current admin emails are encrypted with a single static key (`EMAIL_ENCRYPTION_KEY`). If this key is compromised, all admin data is exposed, and rotation is impossible without a manual, risky re-encryption of the database.
2.  **Security Debt:** The legacy implementation uses direct `openssl` calls and bypasses the modern, audited `ReversibleCryptoService`.
3.  **Dead Code Activation:** The project contains a robust `AdminIdentifierCryptoService` that is currently unused. Activating it unifies the codebase.

**Risk Statement:**
Failure to migrate leaves the "Keys to the Kingdom" (Admin Credentials) protected by the weakest link in the cryptographic chain—a single, non-rotatable environment variable.

**Design Philosophy:**
This plan uses a **Parallel Run (Dual-Write / Dual-Read)** strategy. We will duplicate storage columns to allow the old and new systems to coexist before switching authority. This guarantees rollback safety at every stage.

---

## 2. Legacy Identity Crypto Baseline

### Encryption (Data at Rest)
*   **Format:** Base64 encoded string containing `IV . Tag . Ciphertext`.
*   **Algorithm:** AES-256-GCM (via direct `openssl_encrypt`).
*   **Key Source:** `EMAIL_ENCRYPTION_KEY` (Static Env Var).
*   **Storage:** `admin_emails` table, column `email_encrypted` (Blob/Text).
*   **Deficiency:** No `key_id` stored. No HKDF derivation. Tightly coupled to `AdminController`.

### Blind Indexing (Lookups)
*   **Logic:** `HMAC-SHA256(email, EMAIL_BLIND_INDEX_KEY)`.
*   **Storage:** `admin_emails` table, column `email_blind_index`.
*   **Deficiency:** Key is static. Changing the key invalidates the index, breaking login for all users.

---

## 3. Target Identity Crypto Model

### Canonical Services
1.  **`AdminIdentifierCryptoService`**: The domain wrapper for admin identity operations.
2.  **`CryptoProvider`**: The factory for obtaining cryptographic contexts.
3.  **`KeyRotationService`**: Manages key lifecycle and rotation.

### Target Architecture
*   **Encryption Context:** `identity:email:v1` (Derived via HKDF from `CRYPTO_KEYS`).
*   **Storage Schema:** Split columns to match `ReversibleCryptoService` requirements:
    *   `email_encrypted_v2` (Ciphertext)
    *   `email_iv_v2`
    *   `email_tag_v2`
    *   `email_key_id_v2`
*   **Blind Index Context:** `identity:email_index:v1` (Derived via HKDF) OR `ADMIN_IDENTIFIER_PEPPER` (if keeping static pepper strategy).
    *   *Decision:* We will use `ADMIN_IDENTIFIER_PEPPER` initially to match the existing unused service, but store it in a new column `email_blind_index_v2`.

---

## 4. Phased Migration Plan (Core)

### PHASE 0: Preparation (No Runtime Change)
**Goal:** Prepare the database and configuration for the new crypto model.

1.  **Schema Migration:**
    *   Add columns to `admin_emails` table:
        *   `email_encrypted_v2` (TEXT/BLOB, Nullable)
        *   `email_iv_v2` (TEXT, Nullable)
        *   `email_tag_v2` (TEXT, Nullable)
        *   `email_key_id_v2` (VARCHAR, Nullable)
        *   `email_blind_index_v2` (VARCHAR, Indexed, Nullable)
2.  **Config Validation:**
    *   Ensure `CRYPTO_KEYS` contains at least one active key.
    *   Ensure `ADMIN_IDENTIFIER_PEPPER` is set.

### PHASE 1: Dual-Read / Dual-Decrypt
**Goal:** Enable the application to read from the new format if available, falling back to legacy.

1.  **Update `AdminIdentifierCryptoService`:**
    *   Ensure it can decrypt using `CryptoProvider`.
2.  **Update Read Logic (`AdminController` / Repositories):**
    *   Modify `getEmail()` logic:
        *   **IF** `email_encrypted_v2` is NOT NULL:
            *   Decrypt using `AdminIdentifierCryptoService` (Modern).
        *   **ELSE**:
            *   Decrypt using `EMAIL_ENCRYPTION_KEY` (Legacy).
    *   *Note:* At this stage, v2 columns are empty, so 100% of reads will fallback. This proves the fallback logic works.

### PHASE 2: Dual-Write
**Goal:** Ensure all *new* or *updated* records are written to both formats.

1.  **Update Write Logic (`AdminController::addEmail`):**
    *   Compute Legacy Encryption -> Write to `email_encrypted`.
    *   Compute Legacy Index -> Write to `email_blind_index`.
    *   **AND**
    *   Compute Modern Encryption (via `AdminIdentifierCryptoService`) -> Write to `email_encrypted_v2` (plus iv, tag, key_id).
    *   Compute Modern Index -> Write to `email_blind_index_v2`.
2.  **Verification:**
    *   Create a new admin user. Verify database contains data in BOTH sets of columns.
    *   Verify login works (uses Legacy Index).

### PHASE 3: Backfill / Re-encryption
**Goal:** Migrate existing records to the new format.

1.  **Develop Backfill Script:**
    *   Iterate over all admins where `email_encrypted_v2` is NULL.
    *   Decrypt `email_encrypted` using Legacy Key.
    *   Encrypt plaintext using `AdminIdentifierCryptoService` (Modern Key).
    *   Calculate Blind Index v2.
    *   Update record with v2 data.
2.  **Execution:**
    *   Run script in batches.
    *   Monitor for decryption failures (corrupt legacy data).
3.  **Completion:**
    *   All records now have populated v2 columns.

### PHASE 4: Cutover
**Goal:** Switch authority to the new system.

1.  **Switch Read Authority:**
    *   Update `getEmail()` to **ONLY** read `email_encrypted_v2`.
    *   (Optional) Keep fallback for safety, but log a warning if fallback is triggered.
2.  **Switch Lookup Authority:**
    *   Update `AdminController` / `LoginController` to search by `email_blind_index_v2` instead of `email_blind_index`.
3.  **Disable Legacy Write:**
    *   Stop writing to `email_encrypted` and `email_blind_index`.
    *   *Note:* This breaks rollback capability for *new* data created after this point.

### PHASE 5: Legacy Retirement (Deferred)
**Goal:** Remove legacy code and columns.

1.  **Code Cleanup:** Remove `EMAIL_ENCRYPTION_KEY` and `EMAIL_BLIND_INDEX_KEY` usage.
2.  **Schema Cleanup:** Drop `email_encrypted` and `email_blind_index` columns.
3.  **Config Cleanup:** Remove legacy env vars.

---

## 5. Blind Index Strategy

**Recommendation:** **Parallel Indexes (Option B)**

**Justification:**
*   **Zero Downtime:** We cannot rebuild the index in-place without stopping logins.
*   **Safety:** We can verify the new index works before switching traffic.
*   **Reversibility:** If the new index has issues, we can instantly revert the lookup query to the old column.

**Implementation:**
*   **Legacy:** `HMAC(email, EMAIL_BLIND_INDEX_KEY)` -> `email_blind_index`
*   **Modern:** `HMAC(email, ADMIN_IDENTIFIER_PEPPER)` -> `email_blind_index_v2`
*   *Note:* Ideally, `ADMIN_IDENTIFIER_PEPPER` should be derived from `CRYPTO_KEYS` using HKDF to allow future rotation (by re-indexing), but for this migration, using the static `ADMIN_IDENTIFIER_PEPPER` (as currently defined in the unused service) is a sufficient step forward.

---

## 6. Key Rotation Impact Analysis

**After Migration:**

1.  **Encryption Rotation:**
    *   **Status:** **Fully Supported.**
    *   **Mechanism:** Change `CRYPTO_ACTIVE_KEY_ID`. New writes use the new key. Old data remains decryptable via `key_id` lookup.
    *   **Re-encryption:** Optional background job can re-encrypt old data to the new key if desired (to retire old keys).

2.  **Blind Index Rotation:**
    *   **Status:** **Manual Migration Required.**
    *   **Mechanism:** Blind indexes are deterministic. Changing the key requires a new column (`v3`) and a new backfill process (Phase 0-4 repeat).
    *   **Improvement:** While not "auto-rotatable", the architecture now supports a standard migration path for index rotation, unlike the legacy hardcoded logic.

---

## 7. Failure & Rollback Scenarios

| Scenario                        | Trigger                               | Mitigation / Rollback                                                                        |
|:--------------------------------|:--------------------------------------|:---------------------------------------------------------------------------------------------|
| **Phase 1/2 Code Error**        | Bug in `AdminIdentifierCryptoService` | Revert code deployment. Legacy columns are untouched.                                        |
| **Phase 3 Backfill Corruption** | Script writes bad data to v2 columns  | `UPDATE admin_emails SET email_encrypted_v2 = NULL`. Fix script. Retry. Legacy data is safe. |
| **Phase 4 Read Failure**        | V2 decryption fails in production     | Revert code to Phase 3 (Dual-Read with Legacy Preference).                                   |
| **Phase 4 Lookup Failure**      | Users cannot login (Index mismatch)   | Revert code to Phase 3 (Lookup via Legacy Index).                                            |
| **Silent Data Drift**           | V1 and V2 data diverge during Phase 2 | Add consistency check job: `Decrypt(V1) == Decrypt(V2)`. Alert on mismatch.                  |

---

## 8. Explicit Non-Goals

*   **Password Migration:** This plan does **NOT** touch `password_hash` or `PASSWORD_PEPPER`.
*   **TOTP Migration:** TOTP secrets are a separate domain and should be handled in a separate migration (though they can use the same `CryptoProvider`).
*   **Immediate Key Rotation:** We are enabling rotation capabilities, not forcing a rotation event immediately.
*   **Framework Refactor:** We are not rewriting the entire `AdminController` to a new framework, only swapping the crypto implementation details.
