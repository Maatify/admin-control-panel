# Security Signals Module

**Domain:** Security Signals (Non-Authoritative)
**Namespace:** `Maatify\SecuritySignals`
**Type:** Pure Library

## Purpose
This module implements the **Security Signals** logging domain. It captures security-relevant signals (e.g., login failures, permission denials, anomalies) for monitoring, alerting, and risk investigation.

## Core Characteristics
*   **Domain:** Security Observability.
*   **Nature:** Non-authoritative (Observational).
*   **Failure Semantics:** Best-effort (Fail-open).
*   **Storage:** `security_signals` table.

## Usage
This module provides the infrastructure to persist security signals. It is designed to be used by a Recorder (not included in this library core) or directly by infrastructure adapters.

See `PUBLIC_API.md` for integration details.
