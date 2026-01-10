# 📄 ADR — Notification Module Scope & Coupling

## ADR-ID

`ADR-007-notification-scope-and-history-coupling`

## Status

**ACCEPTED**

## Date

2026-01-10

---

## Context

The Notification module is designed as a **channel-agnostic orchestration system** responsible for:

* Queueing notification intents
* Secure (encrypted) persistence
* Worker-based delivery lifecycle
* Result handling (sent / failed / skipped)
* UX history logging

The module currently operates within the **Admin Control Panel** domain.

---

## Decision

### 1️⃣ History Persistence Is Admin-Coupled (Intentionally)

* Notification history is persisted directly into:

  ```
  admin_notifications
  ```
* The worker maps:

  ```
  entity_id → admin_id
  ```
* The module **assumes entity_type = admin** for history logging.

This coupling is **intentional and accepted** for the current project scope.

---

### 2️⃣ No Channel Implementations in Notification Module

* The module **must not** contain:

  * Email
  * Telegram
  * SMS
  * Push
* Delivery channels are integrated later via adapters / infrastructure layers.

---

### 3️⃣ Extraction as a Standalone Library Is Deferred

The Notification module **is NOT extraction-ready** due to:

* Direct SQL dependency on `admin_notifications`
* Domain-specific assumptions (Admin UX history)

Extraction would require:

* Introducing a `NotificationHistoryWriterInterface`
* Removing direct schema knowledge from the worker

This refactor is **explicitly deferred**.

---

## Consequences

### ✅ Positive

* Clean, stable Notification core
* Channel-agnostic design
* Predictable lifecycle and testability
* No premature abstractions

### ⚠️ Trade-offs

* Cannot be reused for non-admin entities without refactor
* History schema is application-specific

---

## Guardrails (LOCKED)

The following **must not be added** before the Channels phase:

* Channel implementations inside `app/Modules/Notification`
* Business rules (e.g. “send welcome email”)
* Non-admin entity usage
* History abstraction refactors

---

## Future Work (Explicitly Out of Scope)

* `NotificationHistoryWriterInterface`
* Multi-entity history support
* Library extraction

---
