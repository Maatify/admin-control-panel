# Analysis Report: Step-Up / Session / Login Flow

## 1️⃣ CORE QUESTION: WHEN is the Session actually created?

**The session is created DURING the `login` method execution, BEFORE any 2FA/Step-Up redirection.**

*   **Class:** `App\Domain\Service\AdminAuthenticationService`
*   **Method:** `login`
*   **Execution Point:**
    1.  Credentials verified (Blind Index lookup, Verification check, Password hash verify).
    2.  Check for "Must Change Password" (throws exception if true, preventing session creation).
    3.  Upgrade password hash (if needed).
    4.  **`$this->sessionRepository->createSession($adminId)` is called.**
    5.  Audit log written (`login_credentials_verified`).
    6.  Returns `AdminLoginResultDTO` with token.

**Proof (`AdminAuthenticationService.php`):**
```php
        // 3. Verify Password
        // ... (checks)

        // 4. Transactional Login (Upgrade + Session)
        $this->pdo->beginTransaction();
        try {
            // ... (rehash)

            // 4.2 Create Session
            $token = $this->sessionRepository->createSession($adminId);

            // ... (audit)

            $this->pdo->commit();
        }
```

**Conclusion:**
*   Session is created **BEFORE** redirection to `/2fa/verify` or `/2fa/setup`.
*   Session exists in a state where `StepUpGrant` (Scope: LOGIN) is **missing**.
*   `StepUpService::getSessionState` returns `SessionState::PENDING_STEP_UP` immediately after login.
*   A session **IS required** to access `/2fa/setup` and `/2fa/verify` (protected by `SessionGuardMiddleware`).

---

## 2️⃣ CURRENT BUG — CONFIRM OR REJECT

**Observed:** Admin WITHOUT any TOTP secret is redirected to `/2fa/verify`.
**Expected:** Should be redirected to `/2fa/setup`.

**Root Cause Analysis:**

The decision is made in `App\Http\Middleware\SessionStateGuardMiddleware::process`.

```php
        $state = $this->stepUpService->getSessionState($adminId, $sessionId, $context);

        if ($state !== SessionState::ACTIVE) {
            // ... (API check skipped for Dashboard)

            // Web: Redirect to 2FA Setup or Verify
            $response = new \Slim\Psr7\Response();

            if (!$this->totpSecretStore->exists($adminId)) {
                return $response
                    ->withHeader('Location', '/2fa/setup')
                    ->withStatus(302);
            }

            return $response
                ->withHeader('Location', '/2fa/verify')
                ->withStatus(302);
        }
```

**Determination:**
The code strictly redirects to `/2fa/setup` if `!$this->totpSecretStore->exists($adminId)`.
Since the user observes a redirect to `/2fa/verify`, **`$this->totpSecretStore->exists($adminId)` MUST be returning `true`.**

`AdminTotpSecretStore::exists` calls `AdminTotpSecretRepository::get`.
`AdminTotpSecretRepository::get` performs a `SELECT` on `admin_totp_secrets`.

**Conclusion:**
The admin **DOES** have a record in the `admin_totp_secrets` table. The premise that the admin is "WITHOUT any TOTP secret" is contradicted by the behavior of the code, which relies on the database state. The code logic itself is correct; the data state is the cause.

*Note: `UiRedirectNormalizationMiddleware` was investigated as a potential cause (it forces `/2fa/verify` on `STEP_UP_REQUIRED` error), but this only applies if the response is JSON, which `SessionStateGuardMiddleware` only returns for API requests (`$isApi`=true). For `/dashboard`, `$isApi` is false.*

---

## 3️⃣ PASSWORD CHANGE DURING LOGIN

**Flow Analysis:**

1.  **Assumes Active Session?**
    *   **NO.** `AdminAuthenticationService::login` throws `MustChangePasswordException` **before** calling `createSession`.
2.  **Bypasses Step-Up?**
    *   **YES.** Since no session exists, Step-Up is not applicable. Access is granted via the exception flow handling in `LoginController`.
3.  **After Password Change:**
    *   **Is login re-executed?** **YES.** `ChangePasswordController` redirects to `/login`.
    *   **Is session reused?** **NO.** No session was ever created.
    *   **Is 2FA expected?** **YES.** Upon re-login, the user enters the standard flow: Credentials -> Session -> Step-Up (2FA).

**Statement:**
The current flow is **correct** and secure. It prevents a user with an expired/must-change password from obtaining a session (even a restricted one) until the password is changed.

---

## 4️⃣ STEP-UP SERVICE BEHAVIOR

**Analysis:**

*   `StepUpService` is designed to be a **post-login** service (or privilege elevation).
*   It requires an existing session token (`$token` argument in `verifyTotp`, `enableTotp`).
*   `getSessionState` correctly identifies a session as `PENDING_STEP_UP` if the `LOGIN` scope grant is missing or risk context has changed.
*   `issuePrimaryGrant` and `verifyTotp` are consistent in issuing the grant only after verification.

**Consistency:**
The implementation is internally consistent. The system explicitly expects a session **BEFORE** step-up can occur. This aligns with the `SessionGuardMiddleware` protecting the `/2fa/*` routes.

---

## 5️⃣ API / METHOD CONTRACT OUTPUT

### Method: `AdminAuthenticationService::login`
*   **Input:**
    *   `string $blindIndex`
    *   `string $password`
    *   `RequestContext $context`
*   **Output:**
    *   `AdminLoginResultDTO` (contains `token`, `adminId`)
    *   **Throws:** `InvalidCredentialsException`, `AuthStateException`, `MustChangePasswordException`
*   **Side Effects:**
    *   **Creates Session:** YES (if successful and no password change required)
    *   **Writes Audit:** YES (`login_credentials_verified`)
    *   **Writes Grants:** NO (Session created in `PENDING_STEP_UP` state)

### Method: `AdminSessionRepository::createSession`
*   **Input:**
    *   `int $adminId`
*   **Output:**
    *   `string` (Plaintext Token)
*   **Side Effects:**
    *   Inserts row into `admin_sessions`.
    *   Generates random token.

### Method: `StepUpService::getSessionState`
*   **Input:**
    *   `int $adminId`
    *   `string $token`
    *   `RequestContext $context`
*   **Output:**
    *   `SessionState` (`ACTIVE`, `PENDING_STEP_UP`, etc.)
*   **Side Effects:**
    *   None (Read-only).

### Method: `StepUpService::verifyTotp`
*   **Input:**
    *   `int $adminId`
    *   `string $token`
    *   `string $code`
    *   `RequestContext $context`
    *   `?Scope $requestedScope`
*   **Output:**
    *   `TotpVerificationResultDTO` (success bool, error reason)
*   **Side Effects:**
    *   **Writes Audit:** NO (Audit logic for *verification* attempt seems missing or handled by `securityEventRecorder` on failure, but `issuePrimaryGrant` writes audit on success). *Correction: It calls `issuePrimaryGrant` which writes audit.*
    *   **Writes Grants:** YES (Issues `Scope::LOGIN` or requested scope).

### Method: `StepUpService::enableTotp`
*   **Input:**
    *   `int $adminId`
    *   `string $token`
    *   `string $secret`
    *   `string $code`
    *   `RequestContext $context`
*   **Output:**
    *   `bool` (success)
*   **Side Effects:**
    *   **Writes Grants:** YES (Issues `Scope::LOGIN`).
    *   **Persists Secret:** YES (Encrypted into `admin_totp_secrets`).
    *   **Writes Audit:** YES (`stepup_enrolled`).

### Method: `AdminTotpSecretStore::exists`
*   **Input:**
    *   `int $adminId`
*   **Output:**
    *   `bool`
*   **Side Effects:**
    *   None (Read-only DB check).

### Method: `SessionStateGuardMiddleware::process`
*   **Input:**
    *   `ServerRequestInterface $request`
    *   `RequestHandlerInterface $handler`
*   **Output:**
    *   `ResponseInterface`
*   **Side Effects:**
    *   **Redirects:** YES (to `/2fa/verify` or `/2fa/setup` if `PENDING_STEP_UP`).
    *   **Writes Telemetry/Audit:** YES (via `stepUpService->logDenial` if access denied).

---

## 6️⃣ FINAL DELIVERABLE

1.  **Factual Truth:** Sessions are created **immediately after password verification** inside `AdminAuthenticationService::login`. The session exists and is required for the subsequent Step-Up (2FA) flow.
2.  **Exact Root Cause of Bug:** `AdminTotpSecretStore::exists($adminId)` is returning `true`. This confirms that a record exists in the `admin_totp_secrets` table for the affected admin. The application logic correctly branches: if `exists` is true → `/2fa/verify`; if `exists` is false → `/2fa/setup`. The "bug" is a mismatch between the operator's expectation ("Admin has no secret") and the database reality.
3.  **Architectural Consistency:** The design is **consistent**.
    *   Session creation precedes Step-Up.
    *   Middleware (`SessionStateGuard`) enforces Step-Up.
    *   `StepUpService` manages the transition from `PENDING` to `ACTIVE` via grants.
    *   Password change correctly bypasses this by not creating a session.
4.  **Recommendation:**
    *   **Confirm Current Behavior:** The code logic is correct.
    *   **Action:** Verify the database content for `admin_totp_secrets` for the test user. If the user is supposed to be "unenrolled", the row in `admin_totp_secrets` must be deleted. Do not change the code to "fix" this, as the code correctly interprets the presence of a secret as "Enrolled".
