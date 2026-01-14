# Verification Codes: Crypto & Rotation Alignment Audit

## 1. Identity Binding & Storage Model

### Verification Codes Table
*   **Binding:** Binds to `identity_id` (VARCHAR) + `identity_type` (ENUM).
*   **For Admins:** `identity_id` stores the `admins.id` (primary key, integer).
*   **PII Storage:** Minimal. Only the internal ID is stored. The verification code itself is hashed (`code_hash`).

### Admin Email Storage (`admin_emails`)
*   **Binding:** Binds `admin_id` (FK) to Encrypted Data.
*   **Lookup:** Uses `email_blind_index` (deterministic hash with secret salt) for O(1) lookups without decrypting.
*   **Storage:** `email_ciphertext`, `email_iv`, `email_tag`, `email_key_id`.

### Comparison
| Feature | `verification_codes` | `admin_emails` | Alignment |
| :--- | :--- | :--- | :--- |
| **Identifier** | `identity_id` (Admin ID) | `admin_id` (Admin ID) | **MATCH** |
| **PII Exposure** | None (Internal ID only) | Encrypted (AES-GCM) | **SECURE** |
| **Lookup Method** | Direct by Hash or ID | Blind Index | **DECOUPLED** |

## 2. Key Rotation Alignment

**Scenario:** The system rotates the Master Key or the Blind Index Key.

### Impact Analysis
1.  **Admin Email Table:**
    *   Requires re-encryption of `email_ciphertext`.
    *   Requires re-computation of `email_blind_index`.
    *   **Result:** The lookup value (blind index) changes.

2.  **Verification Codes Table:**
    *   Stores `identity_id` (Raw Admin ID).
    *   **Does NOT store** the email address, the blind index, or any encrypted pointer.
    *   **Result:** The OTP records remain valid and linked to the correct user.

### Conclusion: Strong Alignment
The `verification_codes` system is **Rotation-Agnostic**.
*   It does not depend on the stability of the Blind Index.
*   It does not depend on the stability of the Encryption Key.
*   It anchors to the immutable `admins.id`.

**Correction Note:**
The `resend` flow in `EmailVerificationController` *does* use the Blind Index to find the Admin ID. If a rotation happens *during* a request (millisecond window), it might fail, but subsequent requests will use the new key to derive the new blind index, find the same Admin ID, and generate a code for that Admin ID. The OTP itself is unaffected.

## 3. DB Delta Plan (Schema Proposals)

To maintain this alignment while improving lifecycle management:

### Option A: Redis as Truth (Recommended)
*Decouples high-churn OTP data from persistent storage completely.*

**DDL / Actions:**
1.  **Migrate Storage:** Stop writing to `verification_codes`. Write to Redis `otp:admin:{id}:...`.
2.  **Schema Change:**
    ```sql
    -- If keeping table for audit, remove unique constraints that enforce "one active code" logic
    -- as Redis now handles the active state.
    ALTER TABLE verification_codes DROP INDEX idx_active_lookup;
    ALTER TABLE verification_codes ADD INDEX idx_identity_log (identity_type, identity_id, created_at);
    ```

### Option B: MySQL as Truth (Rotation-Aware)
*Ensure `identity_id` remains strictly typed to avoid accidental PII drift.*

**DDL / Actions:**
1.  **Strict Typing:** Ensure `identity_id` column collation matches system defaults to prevent lookup issues if IDs become non-numeric strings in future.
    ```sql
    ALTER TABLE verification_codes MODIFY identity_id VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;
    ```
2.  **Purge Policy (Crucial for Privacy):** Even though PII isn't stored, metadata (who requested OTPs and when) is.
    ```sql
    -- Add TTL-based partitioning or scheduled event (same as Conformance Plan)
    CREATE EVENT purge_old_verification_logs
    ON SCHEDULE EVERY 1 DAY
    DO
      DELETE FROM verification_codes
      WHERE created_at < NOW() - INTERVAL 30 DAY;
    ```
