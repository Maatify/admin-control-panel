# Remember-Me Implementation

**STATUS: FROZEN (Phase C2.2)**
**LOCKED SINCE:** v1.3.6

This document defines the behavior of the "Remember Me" (Persistent Login) functionality.

---

## 1. Split-Token Architecture

The system uses a randomized Split-Token model to prevent database compromise from revealing valid tokens.

- **Selector**: 16 bytes (hex). Used for database lookup. Publicly visible in cookie.
- **Validator**: 32 bytes (hex). Hashed (SHA-256) before storage. The "password" of the token.
- **Cookie Format**: `selector:validator`

---

## 2. Rotation & Persistence

- **Rotation**: Every successful usage of a Remember-Me token **consumes** it.
  - The old token is deleted.
  - A new token (new Selector + new Validator) is issued.
  - The browser cookie is updated.
- **Persistence**:
  - **Database**: Valid for 30 days.
  - **Cookie**: `Max-Age` set to 30 days.

---

## 3. Theft Detection (The Check)

If a malicious actor steals a database backup, they only see the Hashed Validator.
If a malicious actor steals a cookie (Selector:Validator), they can use it **once**.

**The Detection Logic:**
1. User presents `Selector:Validator`.
2. System looks up `Selector`.
3. If `Selector` exists but `Hash(Validator)` does not match stored hash:
   - **Conclusion**: The token was rotated (used) by the legitimate user, meaning the presenter is using an old (stolen) token.
   - **Action**:
     - **CRITICAL ALERT**: `remember_me_theft_suspected`.
     - **Immediate Wipe**: The Compromised Selector is deleted.
     - **Block**: The login attempt is rejected.

---

## 4. Cookie Semantics (Accepted Behavior)

There is a distinct behavioral difference between the `auth_token` cookie issued during **Login** versus **Remember-Me Restoration**:

| Origin | Cookie Type | Max-Age | Behavior |
| :--- | :--- | :--- | :--- |
| **LoginController** | Persistent | Session TTL (e.g. 2 hours) | Survives browser restart until backend expiry. |
| **RememberMeMiddleware** | Session | `null` (Browser Session) | Cleared on browser close. |

**Intentionality**:
This inconsistency is **ACCEPTED** and **FROZEN**.
- When a user logs in via Remember-Me, they receive a Session Cookie.
- If they close the browser, the Session Cookie is lost.
- Upon reopening, `RememberMeMiddleware` runs again, validating the Persistent Remember-Me cookie and issuing a *new* Session Cookie.
- This effectively simulates persistence while maintaining a stricter security posture for the auth token itself.

---

## 5. Non-Supported Features

- **No "Keep me signed in" check for Admin console**: Remember-Me is strictly for convenience on trusted devices, not for keeping the session alive indefinitely without interaction.
- **No parallel active tokens**: A single device (User Agent) context is generally assumed for the rotation chain, though the schema supports multiple independent tokens (one per device).
