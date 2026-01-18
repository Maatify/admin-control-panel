# Admin Create (Registration) + Step-Up (TOTP Scoped) — AS-IS DISCOVERY & GAP ANALYSIS

## PART 1) CURRENT STEP-UP IMPLEMENTATION (AS-IS)

### 1) Middleware
| Middleware | File Path | Responsibility | Applied Where | Status |
| :--- | :--- | :--- | :--- | :--- |
| `ScopeGuardMiddleware` | `app/Http/Middleware/ScopeGuardMiddleware.php` | Checks if current session has specific scope required by route (via `ScopeRegistry`). Returns 403 JSON if missing. | Global (Web + API Groups) | **ACTIVE** (Gap: No Web Redirect) |
| `SessionStateGuardMiddleware` | `app/Http/Middleware/SessionStateGuardMiddleware.php` | Enforces `SessionState::ACTIVE`. Checks `Scope::LOGIN` grant + Risk Match. Redirects to `/2fa/verify` (Web) or 403 (API). | Global (Web + API Groups) | **ACTIVE** |
| `AdminContextMiddleware` | `app/Http/Middleware/AdminContextMiddleware.php` | Hydrates `AdminContext` from `admin_id` attribute. | Global (Web + API Groups) | **ACTIVE** |
| `SessionGuardMiddleware` | `app/Http/Middleware/SessionGuardMiddleware.php` | Validates `auth_token` cookie/header via `SessionValidationService`. Sets `admin_id`. | Global (Web + API Groups) | **ACTIVE** |

### 2) Services
| Service | File Path | Responsibility | Active/Legacy |
| :--- | :--- | :--- | :--- |
| `StepUpService` | `app/Domain/Service/StepUpService.php` | Verifies TOTP (`verifyTotp`), Issues Grants (`issuePrimaryGrant`, `issueScopedGrant`), Checks State (`getSessionState`, `hasGrant`), Enforces Risk (`getRiskHash`). | **ACTIVE** |
| `TwoFactorController` | `app/Http/Controllers/Web/TwoFactorController.php` | Handles Web 2FA Verification Logic. Delegates to `StepUpService`. Hardcoded to `Scope::LOGIN`. | **ACTIVE** |
| `UiStepUpController` | `app/Http/Controllers/Ui/UiStepUpController.php` | Wrapper for `TwoFactorController` for `/2fa/verify` routes. | **ACTIVE** |

### 3) Storage
- **Table:** `step_up_grants`
- **Fields Used:** `admin_id`, `session_hash`, `scope`, `risk_context_hash`, `created_at`, `expires_at`, `single_use` (checked but always false in creation).
- **TTL Handling:**
  - `expires_at` column in DB.
  - Checked in `StepUpService::hasGrant` (`$grant->expiresAt < new DateTimeImmutable()`).
  - Checked in `StepUpService::getSessionState`.
- **Risk Binding:**
  - Computed in `StepUpService::getRiskHash` as `hash('sha256', $context->ipAddress . '|' . $context->userAgent)`.
  - Checked against `risk_context_hash` column on every access (`hasGrant`, `getSessionState`). Mismatch triggers revocation.

### 4) Session State Handling
- **Computed:** `StepUpService::getSessionState`.
- **States:**
  - `SessionState::ACTIVE`: Primary Grant (`Scope::LOGIN`) exists, not expired, Risk Hash matches.
  - `SessionState::PENDING_STEP_UP`: Otherwise.
- **Enforcement:** `SessionStateGuardMiddleware` blocks access if not `ACTIVE`.

### 5) Error Behavior (AS-IS)
- **API (JSON):**
  - **403 Forbidden**
  - Body: `{"code": "STEP_UP_REQUIRED", "scope": "login"}` (from `SessionStateGuard`) or `{"code": "STEP_UP_REQUIRED", "scope": "security"}` (from `ScopeGuard`).
- **Web (HTML):**
  - **Redirect (302)** to `/2fa/verify` (Only in `SessionStateGuard` / Primary Login).
  - **GAP:** `ScopeGuardMiddleware` returns **403 JSON** even for Web requests. No redirect for Scoped Step-Up failure.

---

## PART 2) ENTRY POINTS THAT REQUIRE STEP-UP (AS-IS)

### Enforced by `SessionStateGuardMiddleware` (Scope::LOGIN)
- **All Routes** in `protectedGroup` (Web and API) require `SessionState::ACTIVE`.

### Enforced by `ScopeGuardMiddleware` (Specific Scopes via `ScopeRegistry`)
| Route Name | Route Path | Scope Required | Enforcement |
| :--- | :--- | :--- | :--- |
| `admin.create` | `POST /api/admins/create` | `Scope::SECURITY` | **Active** (Returns 403 JSON if missing) |
| `email.verify` | `POST /api/admins/{id}/emails/verify` | `Scope::SECURITY` | **Active** |
| `role.assign` | (Various) | `Scope::ROLES_ASSIGN` | **Active** |
| `audit.read` | (Various) | `Scope::AUDIT_READ` | **Active** |

### GAPS (Should be Sensitive but Missing/Default)
- **Admin Email Add:** `POST /api/admins/{id}/emails` (`email.add`) is **NOT** in `ScopeRegistry`. Defaults to `Scope::LOGIN` (Primary only).
- **Admin Password Set:** No endpoint exists.

---

## PART 3) RETURN FLOW (CRITICAL) — AS-IS ONLY

**Status: NON-EXISTENT**
- **No Intent/Scope Passing:** `TwoFactorController::doVerify` accepts `code` only. It calls `verifyTotp` with hardcoded `Scope::LOGIN`.
- **No Return Destination:** `TwoFactorController::doVerify` redirects to `/dashboard` on success.
- **ScopeGuard:** Simply blocks with 403. Does not initiate any flow to acquire the grant.

Explicit Statement: **"Step-Up currently has NO return flow mechanism in code."**

---

## PART 4) UI STATUS (AS-IS)

**Status: EXISTS (PARTIAL / LOGIN ONLY)**
- **Controller:** `App\Http\Controllers\Web\TwoFactorController` (via `UiStepUpController`).
- **Template:** `templates/pages/2fa_verify.twig`.
- **Route:** `GET /2fa/verify`, `POST /2fa/verify`.
- **Capability:** Verifies TOTP for **Primary Session Activation (`Scope::LOGIN`) only**.
- **Limitation:** Cannot handle Scoped Grants (e.g., Security, Export). Logic is hardwired to `Scope::LOGIN`.

---

## PART 5) ADMIN CREATE / REGISTRATION (AS-IS)

### 1) Endpoints
- **Create ID:** `POST /api/admins/create` (`AdminController::create`).
  - Inputs: None (pure POST).
  - Validation: None.
  - Returns: `{ "adminId": 123, "createdAt": "..." }`.
- **Add Email:** `POST /api/admins/{id}/emails` (`AdminController::addEmail`).
  - Inputs: `email`.
  - Validation: `AdminAddEmailSchema`.
  - Storage: `admin_emails` (encrypted + blind index).

### 2) Password Handling
- **Status: MISSING**
- **Logic:** `AdminAuthenticationService` and `AdminPasswordRepository` exist for **Login** and **Rehash**, but there is **NO code path to set/insert a password** for a new admin.
- `AdminRepository::create` inserts `NOW()` only.

### 3) Logging
- `AdminController` uses `AdminActivityLogService` (Activity Log / Non-Authoritative).
  - `ADMIN_CREATE`
  - `ADMIN_EMAIL_ADDED`
- **GAP:** No Authoritative Audit Log (Outbox) entry for Admin Creation in the Controller.

---

## PART 6) GAPS (NO SOLUTIONS)

1.  **Missing Password Mechanism:** Impossible to create a usable admin account (no password set).
2.  **Broken Scoped Step-Up on Web:** `ScopeGuardMiddleware` returns 403 JSON for web requests instead of redirecting to UI.
3.  **Missing Return Flow:** No mechanism to preserve intent (destination/scope) across the Step-Up flow. `TwoFactorController` redirects blindly to `/dashboard`.
4.  **UI Scope Limitation:** `2fa_verify.twig` and `TwoFactorController` support `Scope::LOGIN` only. Unable to request/verify `Scope::SECURITY`.
5.  **Incomplete Protection:** `email.add` endpoint is missing from `ScopeRegistry` (requires only Login, not Security).
6.  **Missing Audit:** Admin Creation/Email Addition logs to Activity Log only, not Authoritative Audit Outbox.

---

## PART 7) FINAL CONFIRMATION
- No code was written.
- No design proposals were made.
- This report reflects AS-IS state only.
