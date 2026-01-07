# 🔒 Authentication Architecture & Token Semantics

This document defines the **canonical authentication and session security model** for the Admin Control Panel.

It is a **normative security specification**, not an implementation guide.
Any deviation from this document MUST be treated as a **security-breaking change**.

---

## 1. Authentication Model (Canonical)

### 1.1 Session-Only Authentication

The system enforces **session-only authentication**.

* All authenticated requests are authorized **exclusively** via a server-issued session.
* The session identifier is transported using a secure HTTP cookie named `auth_token`.
* No other authentication mechanisms are supported.

Explicitly disallowed:

* Stateless authentication (JWT, API keys)
* Authorization headers
* Bearer tokens
* Dual authentication paths

This rule applies uniformly to:

* Web UI routes
* Internal API routes

> **API vs Web is a response-format distinction only.**
> It does not affect how authentication is performed.

---

## 2. Middleware Pipeline

### 2.1 Canonical Middleware Order

Protected routes MUST use the following middleware pipeline, in order:

1. **RememberMeMiddleware**
2. **SessionGuardMiddleware**
3. **SessionStateGuardMiddleware**
4. **ScopeGuardMiddleware**

This order is mandatory.

### 2.2 Middleware Responsibilities

#### RememberMeMiddleware

* Executes first.
* Detects the presence of a `remember_me` cookie **only when no active session exists**.
* Validates the remember-me token.
* Restores a new session if valid.
* Immediately rotates the remember-me token.

#### SessionGuardMiddleware

* Validates the `auth_token` session cookie.
* Resolves the authenticated `admin_id`.
* Rejects invalid, expired, or revoked sessions.

#### SessionStateGuardMiddleware

* Enforces session state correctness.
* Blocks access when the session is not in `ACTIVE` state.
* Ensures 2FA / Step-Up requirements are satisfied.

#### ScopeGuardMiddleware

* Enforces RBAC scopes and permissions.
* Executes only after identity and state are validated.

---

## 3. Route Coverage Rules

* **Guest routes** use `GuestGuardMiddleware` to block authenticated users.
* **Protected UI routes** MUST use the full middleware pipeline.
* **Protected API routes** use the same pipeline, without RememberMe auto-restoration.
* **Step-Up routes** intentionally exclude SessionStateGuard to avoid redirect loops.
* **Webhook routes** are explicitly excluded and secured by alternative mechanisms.

No route may partially bypass authentication.

---

## 4. Token Semantics

### 4.1 Session Token (`auth_token`)

**Purpose:** Active authentication

* Transported as: HTTP cookie (`auth_token`)
* Format: Opaque, cryptographically random string
* Storage (server-side): SHA-256 hash only
* Verification: `hash(input) === stored_hash`
* TTL: Short-lived (e.g., 2 hours)
* Rotation: On login and on remember-me restoration

The raw session token is **never stored at rest**.

---

### 4.2 Remember-Me Token (`remember_me`)

**Purpose:** Session restoration convenience

* Transported as: HTTP cookie (`remember_me`)
* Format: `selector:validator`

| Component | Description           |
| --------- | --------------------- |
| Selector  | Non-secret lookup key |
| Validator | High-entropy secret   |

#### Storage Model

* Selector: stored in plaintext
* Validator: stored as SHA-256 hash only
* Bound to:

    * `admin_id`
    * user-agent hash
    * expiration timestamp

#### Behavior

* Single-use
* Rotated on every successful auto-login
* Deleted on logout or invalid use

---

## 5. Remember-Me Flow (Authoritative)

1. User logs in successfully.
2. `RememberMeService::issue()` generates selector and validator.
3. Hashed validator is stored server-side.
4. Cookie `remember_me` is set on client.

### Auto-Login

1. Incoming request has **no `auth_token`** but has `remember_me`.
2. RememberMeMiddleware validates selector and validator.
3. Old remember-me token is deleted.
4. New session is created.
5. New remember-me token is issued (rotation).
6. Session state is set to `PENDING_STEP_UP`.

> Remember-me **does not bypass 2FA**.

---

## 6. Encryption vs Hashing

### 6.1 Encryption

* **Not used** for authentication tokens.
* Tokens are not encrypted or decrypted.

### 6.2 Hashing

* **Mandatory** for all tokens stored at rest.
* One-way hashing (SHA-256) is used.

Security implications:

* Database compromise does not reveal usable tokens.
* Token comparison is deterministic and non-reversible.

---

## 7. Explicit Non-Goals

The following are intentionally NOT supported:

* JWT or stateless tokens
* Authorization header authentication
* Bearer tokens
* Trusted-device bypasses
* Long-lived active sessions
* Shared tokens between session and remember-me

Any attempt to introduce these is a **security violation**.

---

## 8. Security Guarantees

This architecture guarantees:

* Single authoritative authentication source
* No dual trust paths
* No stateless access
* No 2FA bypass via remember-me
* Deterministic session lifecycle

---

## 9. Change Control

Any modification to:

* token semantics
* middleware order
* remember-me behavior

MUST be treated as a **breaking security change** and requires:

* explicit documentation
* security review
* audit approval

---

---

## Appendix A — Optional Future Hardening (NOT IMPLEMENTED)

The following items are **documented for future consideration only**.

They are **NOT part of the active authentication model** and are
**explicitly excluded from the current security guarantees**.

Implementing any of the following requires:
- a new dedicated phase
- architectural approval
- privacy impact review
- explicit configuration controls

---

### A.1 IP / Device Binding Extensions (Optional)

**Concept:**
In addition to the existing User-Agent binding used in `remember_me` tokens,
the system MAY optionally introduce additional device or network binding.

Possible signals include:
- IP address (exact or subnet-based)
- Device fingerprint (e.g., via FingerprintJS or equivalent)
- Hybrid risk scoring (IP + UA + behavior)

**Intended Purpose:**
- Reduce risk of session hijacking
- Detect anomalous token reuse
- Strengthen protection for internal APIs that do not support remember-me

**Expected Behavior (If Implemented):**
- On significant IP or fingerprint change:
  - Invalidate the restored session
  - Force re-authentication or Step-Up verification
- Enforcement MUST be fail-closed for suspicious transitions

**Privacy & Stability Considerations:**
- IP binding MUST be tolerant to NAT, mobile networks, and VPN changes
- Fingerprinting MUST be:
  - optional
  - configurable
  - explicitly disclosed
- Hard binding MUST NOT be enabled by default

**Canonical Status:**
- ❌ Not implemented
- ❌ Not enforced
- ❌ Not required for compliance
- ❌ Must NOT affect current audit conclusions
- ✅ Documented to prevent architectural drift

Any attempt to implement this without a dedicated phase
is considered a security policy violation.

---

**Status:** LOCKED
**Scope:** Admin Control Panel
**Classification:** Security-Critical Architecture Document
