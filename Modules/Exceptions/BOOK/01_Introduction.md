# Introduction

`maatify/exceptions` is a strictly typed, immutable taxonomy for PHP application errors.

## Purpose

The primary goal of this library is to solve "Taxonomy Drift"—the tendency for applications to lose semantic meaning in their error handling over time.

By enforcing a strict taxonomy and guarding override capabilities, this library ensures that:
1.  **System errors remain System errors** (even when wrapped).
2.  **Client errors remain Client errors** (even when re-thrown).
3.  **Monitoring tools receive accurate signals** about system health.

## Philosophy

*   **Exceptions should be semantic:** "User not found" is a different category than "Database offline".
*   **Taxonomy should be immutable:** A developer should not be able to arbitrarily change the category of an exception at runtime.
*   **Severity should never be downgraded:** Critical failures must always bubble up as critical failures.

## Scope

This library provides:
*   A base `MaatifyException` class.
*   Strictly typed Enums for Categories and Error Codes.
*   Concrete exception classes for common scenarios (Validation, Auth, System, etc.).
*   Logic for safe overrides and escalation.

## Non-Goals

This library does *not* provide:
*   HTTP handling or middleware (it is framework-agnostic).
*   Logging implementations (it is PSR-3 compatible but does not implement a logger).
*   Automatic error reporting (it provides the *data* for reporting, not the transport).
