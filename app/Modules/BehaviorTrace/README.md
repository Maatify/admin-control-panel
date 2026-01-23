# BehaviorTrace (Operational Activity)

## Overview

**BehaviorTrace** is the canonical implementation of the **Operational Activity** logging domain.

It records **state changes (mutations)** and operational actions performed by users, admins, or systems.
It answers the question: *"Who changed what?"*

## Domain Authority

*   **Domain:** Operational Activity
*   **Intent:** Track mutations (Create, Update, Delete, Approve, Reject).
*   **Storage:** `operational_activity` (MySQL).
*   **Reference:** `docs/architecture/logging/LOG_DOMAINS_OVERVIEW.md`

## Hard Rules

*   **Mutations Only:** Do NOT log reads, views, exports, or navigation here. (Use `AuditTrail` for that).
*   **Non-Governance:** Do NOT log critical security posture changes here. (Use `AuthoritativeAudit` for that).
*   **Fail-Open:** Failures to log must not crash the application.

## Key Features

*   **Structure:** 1:1 Sibling of `DiagnosticsTelemetry`.
*   **Inputs:** `action`, `actor`, `context`.
*   **Safety:** 64KB metadata limit, auto-truncation of strings.

## Usage

See `PUBLIC_API.md` for integration details.
