# Verification Codes: Conformance Matrix

## 1. Conformance Matrix (Canonical Spec)

| Requirement Category | Spec Requirement | Implementation Status | Evidence / Notes |
| :--- | :--- | :--- | :--- |
| **OTP Storage** | Hashed-only storage | **PASS** | Stored as `code_hash` (SHA-256) in `verification_codes` table. `plainCode` is ephemeral DTO only. |
| **TTL** | Enforce TTL | **PASS** | `expires_at` column checked in `VerificationCodeValidator::validate` and `validateByCode`. |
| **Max Attempts** | Max attempts limit | **PASS** | `attempts` vs `max_attempts` checked in `VerificationCodeValidator`. |
| **One-Time Use** | Invalidate after use | **PASS** | `markUsed` sets status to 'used' upon successful verification. |
| **Purge Strategy** | Auto-purge expired/used | **FAIL** | No automatic purging mechanism found. `expireAllFor` only updates status to 'expired'. Rows accumulate indefinitely. |
| **Redis Policy** | Fail-Closed; No MySQL fallback | **DEVIATION** | System uses MySQL as the **primary** and **only** store (`PdoVerificationCodeRepository`). Redis is not involved in this flow. |

## 2. Redis Unavailability Policy

**Spec Requirement:** Fail-Closed; no MySQL fallback for OTP/scopes.

**Current Architecture:**
- **Status:** **Non-Compliant** (Architectural Mismatch).
- **Analysis:** The current implementation relies entirely on MySQL (`verification_codes` table). There is no Redis implementation for OTPs.
- **Implication:** The system relies on SQL ACID guarantees rather than Redis ephemeral storage. While this ensures durability, it deviates from a high-speed, ephemeral-first architecture usually required for OTPs to avoid DB write load.
- **Fail-Closed Behavior:** Since MySQL is the primary store, if MySQL is unavailable, the system fails closed (exception thrown), which effectively meets the "Fail-Closed" requirement, but via a different architectural path.

## 3. Broken Flows Discovered

### Critical: Resend Flow Generates Code but No Dispatch
**Location:** `App\Http\Controllers\Web\EmailVerificationController::resend`

**Description:**
The `resend` method performs the following actions:
1.  Derives blind index from email.
2.  Lookups Admin ID.
3.  Calls `generator->generate(...)`.
4.  Redirects the user.

**The Failure:**
The `generate` method returns a `GeneratedVerificationCode` DTO containing the plaintext code, but the controller **ignores this return value**. There is no subsequent call to an Email Dispatcher or Queue.

**Result:**
The code is generated and stored in the database, but **never sent to the user**. The user receives no email.

```php
// App/Http/Controllers/Web/EmailVerificationController.php

if ($adminId !== null) {
    try {
        // CODE GENERATED, BUT RETURN VALUE IGNORED
        $this->generator->generate(IdentityTypeEnum::Admin, (string)$adminId, VerificationPurposeEnum::EmailVerification);
        // MISSING: $mailer->send($email, $code->plainCode);
    } catch (\Exception $e) {
        // ...
    }
}
```

---

## 4. DB Delta Plan (Schema Proposals)

### Option A: Redis as Truth (Recommended for Spec Alignment)
*Move active OTPs to Redis. Use MySQL only for audit/history or remove entirely.*

**DDL / Actions:**
1.  **New Redis Key Structure:**
    *   `otp:{identity_type}:{identity_id}:{purpose}` -> `{hash, attempts, max_attempts, expires_at}`
    *   TTL set on Redis key = Policy TTL.
2.  **MySQL Schema Change (Legacy/Archive Mode):**
    ```sql
    -- Drop uniqueness constraint on active lookups as MySQL is no longer authoritative for "active" state
    DROP INDEX idx_active_lookup ON verification_codes;

    -- Optional: Rename table to indicate it is just a log
    RENAME TABLE verification_codes TO verification_code_logs;

    -- Optional: Remove code_hash if strict ephemeral policy requires it never hits disk
    ALTER TABLE verification_code_logs DROP COLUMN code_hash;
    ```

### Option B: MySQL as Truth (Rotation-Aware + Enforce Purge)
*Keep MySQL but fix accumulation issues.*

**DDL / Actions:**
1.  **Add Partitioning (Time-based):**
    ```sql
    -- Requires modifying primary key to include created_at for partitioning
    ALTER TABLE verification_codes DROP PRIMARY KEY, ADD PRIMARY KEY (id, created_at);

    ALTER TABLE verification_codes PARTITION BY RANGE (TO_DAYS(created_at)) (
        PARTITION p_old VALUES LESS THAN (TO_DAYS('2023-01-01')),
        PARTITION p_current VALUES LESS THAN MAXVALUE
    );
    ```
2.  **Add Purge Column (for soft deletes / claimed purge):**
    ```sql
    ALTER TABLE verification_codes ADD COLUMN purged_at DATETIME NULL DEFAULT NULL;
    CREATE INDEX idx_purged_at ON verification_codes (purged_at);
    ```
3.  **Scheduled Event (if using MySQL Event Scheduler):**
    ```sql
    CREATE EVENT purge_expired_otps
    ON SCHEDULE EVERY 1 HOUR
    DO
      DELETE FROM verification_codes
      WHERE (status IN ('used', 'expired', 'revoked') OR expires_at < NOW())
      AND created_at < NOW() - INTERVAL 7 DAY;
    ```
