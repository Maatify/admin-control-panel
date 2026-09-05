# ADR-009: Telegram Delivery as Independent Channel Queue

**Status:** ACCEPTED / LOCKED
**Date:** 2026-01-11
**Phase:** Post-Phase 8 (Delivery Architecture)
**Scope:** Messaging / Async Delivery
**Deciders:** Architecture Board
**Supersedes:** None
**Related:**

* ADR-007 Notification Scope & Admin-Coupled History
* Phase 8 — Notification Intent
* Phase 9 — Delivery Execution

---

## 1️⃣ Context

The system currently supports **asynchronous email delivery** using a dedicated, encrypted queue (`email_queue`) with a strict separation between:

* **Notification Intent** (why to notify)
* **Delivery Execution** (how to deliver)

Telegram was previously introduced as a **notification channel**, creating architectural pressure to reuse notification delivery infrastructure for Telegram.

This raised critical concerns:

* Tight coupling between Notification logic and Telegram transport
* Mixed responsibilities (routing vs delivery)
* Risk of cross-channel leakage (Telegram constraints leaking into Email/Notification design)
* Difficulty extending to future channels (SMS, Push) safely

---

## 2️⃣ Problem Statement

Should Telegram delivery be implemented as:

* **Option A:** A sub-channel inside the Notification Delivery system
* **Option B:** An independent delivery module with its own queue and lifecycle

---

## 3️⃣ Decision

**We choose Option B.**

> **Telegram delivery SHALL be implemented as an independent, channel-owned delivery system with its own encrypted queue, worker, and transport layer.**

Notifications MAY enqueue Telegram delivery jobs, but Telegram delivery is **not owned by** the Notification system.

---

## 4️⃣ Architectural Decision

### ✅ Telegram is a Delivery Channel, not a Notification System

* Telegram handles **message transport only**
* Notifications handle **intent, preference, and history**
* The integration point is **enqueue only**

```
Notification → TelegramQueueWriter → telegram_queue → TelegramWorker → Telegram API
```

---

### ✅ Independent Queue Contract

A dedicated database table **`telegram_queue`** is introduced with:

* Encrypted chat_id
* Encrypted message payload
* Channel-specific options (parse_mode, preview flags)
* Independent retry & failure semantics
* Scheduling support

The schema is considered **LOCKED** once introduced.

---

### ✅ Strict Responsibility Boundaries

| Layer                 | Responsibility                |
|-----------------------|-------------------------------|
| Notification Intent   | Why to notify                 |
| Routing / Preferences | Which channels                |
| Telegram Queue        | Delivery execution            |
| Telegram Worker       | Retry, rate-limit, API errors |
| Telegram Transport    | HTTP + Bot API                |

---

## 5️⃣ Explicitly Forbidden

The following are **NOT allowed**:

* ❌ Telegram worker reading from `notification_delivery_queue`
* ❌ Notification system calling Telegram API directly
* ❌ Shared workers between Email and Telegram
* ❌ Telegram logic deciding notification routing
* ❌ Telegram storing notification preferences or severity

Any of the above is considered a **hard architectural violation**.

---

## 6️⃣ Consequences

### 🟢 Positive

* Clear separation of concerns
* Reduced coupling
* Easier testing and observability
* Safe future extension (SMS, Push = same model)
* Cleaner failure isolation

### 🔴 Costs

* Additional queue table
* Additional worker process
* Slight duplication of queue logic (intentional)

---

## 7️⃣ Compliance & Invariants

* Encryption model MUST match Email (AES-GCM, key-versioned)
* Delivery failures MUST be recorded as state, not thrown
* No plaintext identifiers at rest
* No cross-channel schema reuse

---

## 8️⃣ Implementation Order (MANDATORY)

1. **Database schema (`telegram_queue`) — LOCK**
2. ADR-009 accepted (this document)
3. Module contracts
4. Queue writer
5. Worker
6. Transport adapter

Any deviation from this order requires a **new ADR**.

---

## 9️⃣ Final Verdict

> **Telegram is an independent delivery subsystem.
> Notifications enqueue — Telegram delivers.
> No shared ownership. No shared queues.**

**ADR-009 is hereby ACCEPTED and LOCKED.**

---
