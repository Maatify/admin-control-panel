# Notification Delivery Architecture (PENDING)

> **Status:** INTENTIONALLY PENDING / DESIGN PHASE
> **Context:** This subsystem is actively entering its design phase.
> **Scope:** Email Delivery, Queue Management, Encryption

---

## ⚠️ Status: Pending Implementation

This document outlines the **intended direction, constraints, and non-goals** for the Notification Delivery subsystem.
It is **NOT** a complete implementation specification yet.

**Current State:**
*   The contracts (`NotificationDeliveryDTO`, `DeliveryResultDTO`) are defined in concept.
*   The actual infrastructure (Queues, Encryption, Workers) is **NOT** fully specified or implemented in this document.
*   Consult `docs/PROJECT_CANONICAL_CONTEXT.md` for the authoritative architectural constraints (Encryption Contexts, Queue Tables).

---

## 🎯 Intended Direction

The goal is to provide a robust, asynchronous delivery mechanism that:
1.  **Decouples Intent from Execution**: The application generates "Intent" (Phase 8), and this subsystem handles "Delivery" (Phase 9).
2.  **Enforces Encryption**: All sensitive payload data must be encrypted at rest in the queue.
3.  **Ensures Reliability**: Delivery should be retriable and fail-safe.

## 🚫 Constraints (Non-Negotiable)

*   **Asynchronous Only**: No synchronous email sending.
*   **Encrypted Storage**: Queue tables must use the specified Crypto Contexts (`email:recipient:v1`, etc.).
*   **No Business Logic**: This layer only delivers; it does not decide *who* to notify or *what* to say.

## 🛑 Non-Goals

*   This subsystem does **NOT** handle user preferences (Routing Layer).
*   This subsystem does **NOT** generate content (Templates are handled at the Intent layer).

---

**Next Steps:**
*   Finalize the `email_queue` schema.
*   Implement the `PdoEmailQueueWriter`.
*   Implement the CLI Worker.
