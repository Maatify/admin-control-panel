# ADR-014: Verification Notification Dispatcher

**Status:** Accepted
**Date:** 2026-01-20
**Project:** `maatify/admin-control-panel`
**Scope:** Verification Code Delivery (Email / future channels)

---

## 1. Context

The system generates **verification codes** for multiple purposes, including:

* Email verification
* Step-Up / MFA flows (future)
* Password reset (future)

These verification codes must be **delivered asynchronously** via external channels such as:

* Email (current)
* Telegram (planned)
* SMS / Push (future)

Prior to this decision, there was a risk of:

* Controllers directly writing to infrastructure queues
* Tight coupling between HTTP layer and delivery mechanisms
* Inconsistent handling of delivery failures
* Lack of a unified abstraction for verification notifications

The system also enforces **strict logging rules**, where:

* Delivery failures are **not business events**
* Only PSR-3 diagnostic logging is allowed for infra failures
* Security Events are reserved for verification failures, not delivery issues

---

## 2. Decision

A unified **Verification Notification Dispatcher** is introduced.

### 2.1 Component

```
App\Application\Verification\VerificationNotificationDispatcher
```

### 2.2 Responsibilities

The dispatcher is responsible for:

* Routing verification notifications based on `VerificationPurposeEnum`
* Preparing delivery payloads
* Writing to asynchronous delivery queues
* Abstracting delivery channels from controllers

### 2.3 Explicit Rules

* Controllers **MUST NOT**:

    * Write directly to `email_queue`
    * Write directly to `telegram_queue`
    * Perform encryption or delivery logic

* Controllers **MUST**:

    * Generate verification codes
    * Call the dispatcher exactly once per generation

* The dispatcher **MUST**:

    * Be best-effort
    * Never affect control flow
    * Never throw business exceptions

---

## 3. Delivery Model

### 3.1 Asynchronous Only

All verification notifications are delivered via **async queues**:

| Channel  | Queue Table      |
|----------|------------------|
| Email    | `email_queue`    |
| Telegram | `telegram_queue` |
| Future   | Channel-specific |

No synchronous delivery is permitted.

---

### 3.2 Email Delivery

For `VerificationPurposeEnum::EmailVerification`:

* Payload is written to `email_queue`
* Recipient and payload are encrypted
* Template key: `email_verification_code`
* Language is provided by the controller

---

## 4. Identity Handling

* `identityType` is provided as `IdentityTypeEnum`
* `identityId` is normalized to **string**
* This matches database representation and allows future non-numeric identifiers

**Conversion responsibility:**

* Application layer passes string
* Infrastructure stores string
* Domain identity remains numeric where applicable

---

## 5. Logging & Failure Semantics

### 5.1 Delivery Failures

* Delivery failures are **infrastructure concerns**
* Failures are logged using **PSR-3 only**
* Severity: `warning`
* No Security Event
* No Audit Log
* No Activity Log

Example:

```php
$this->logger->warning(
    'Verification notification dispatch failed',
    [
        'purpose' => $purpose->value,
        'identity_type' => $identityType->value,
        'identity_id' => $identityId,
        'exception' => $e,
    ]
);
```

---

### 5.2 Verification Failures

Verification failures (invalid code, subject not found, expired code) are:

* Logged as **Security Events**
* Recorded by controllers
* Outside the responsibility of the dispatcher

---

## 6. Extensibility

This design enables:

* Adding Telegram delivery without touching controllers
* Adding SMS / Push delivery
* Supporting multi-language templates
* Centralized throttling or routing logic

Future channels MUST be added inside the dispatcher.

---

## 7. Consequences

### 7.1 Positive

* Clear separation of concerns
* Controllers remain application-level only
* Unified verification delivery architecture
* Consistent encryption and queuing
* Audit-safe and security-compliant logging

### 7.2 Trade-offs

* Slight increase in indirection
* Requires dispatcher registration in container

These trade-offs are accepted.

---

## 8. Enforcement

* Any direct queue write from controllers is a **hard violation**
* Any synchronous verification delivery is forbidden
* All new verification purposes MUST use the dispatcher

---

## 9. Status

```
ACCEPTED
```

This decision is **final** and supersedes any prior ad-hoc delivery logic.

---

