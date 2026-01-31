# Observability and UX Hooks

## Overview

This document outlines the architecture for **Observability** and **Admin UX Hooks**.

The goal is to bridge the gap between low-level system logs (Audit Logs, Security Events) and high-level Admin UX needs (Activity Feeds, Action History).

## Distinction between Audit Logs and Activity Feed

### Audit Logs (The Source of Truth)

*   **Purpose:** Compliance, security debugging, non-repudiation.
*   **Storage:** `audit_logs` table.
*   **Format:** Normalized, machine-readable.
*   **Target Type:** `VARCHAR` string (e.g., `'admin'`, `'product'`). Not an Enum to allow extension without schema migration.
*   **Changes:** JSON blob of before/after states.

### Activity Feed (The UX Projection)

*   **Purpose:** Human readability, admin dashboard, "Who did what".
*   **Storage:** None (Computed/Mapped on read).
*   **Interface:** `AdminActivityQueryInterface`.
*   **Data Object:** `AdminActivityDTO`.
*   **Source:** Projections from `audit_logs`.

## Architecture Components

### 1. Metadata Contracts (`AdminActionMetadataInterface`)

*   Defines a standard way for Actions to describe themselves *before* execution if needed.
*   Purely descriptive; no side effects.

### 2. Action Descriptors (`AdminActionDescriptorDTO`)

*   Immutable value object describing an action context.
*   Used for passing context between layers without tight coupling.

### 3. Activity Mapper (`AdminActivityMapper`)

*   **Responsibility:** Converts raw `audit_logs` database rows into `AdminActivityDTO` objects.
*   **Logic:**
    *   Validation of types (ensuring `actor_admin_id` is an integer, etc.).
    *   Decoding JSON `changes`.
    *   Converting `occurred_at` string to `DateTimeImmutable`.
*   **Constraint:** Does *not* format text for the UI (e.g., doesn't generate "Admin X deleted User Y"). It provides the structured data so the UI can decide how to render it.

### 4. Read-Only Query Repository (`AdminActivityQueryRepository`)

*   **Interface:** `AdminActivityQueryInterface`.
*   **Methods:**
    *   `findByActor(int $adminId)`
    *   `findByTarget(string $targetType, int $targetId)`
*   **Behavior:**
    *   Strictly read-only.
    *   Returns DTOs, not arrays.
    *   No complex joins (keeps query performance predictable).

## Design Decisions

### Why `target_type` remains a String

We explicitly avoided PHP Enums or Database Enums for `target_type`. This allows plugins or future modules to introduce new target types (e.g., `'order'`, `'invoice'`) without:
1.  Modifying the `audit_logs` table schema.
2.  Modifying a central Enum class in the core domain.

### Why separate `AuditLogRepository` and `AdminActivityQueryRepository`?

*   **CQRS Principle:** Separation of Command (Writing logs) and Query (Reading feeds).
*   `AuditLogRepository` is for **writing** (High throughput, append-only).
*   `AdminActivityQueryRepository` is for **reading** (UI needs, filtering, projection).
*   This allows us to optimize them independently (e.g., moving old audit logs to cold storage while keeping recent activity hot).

## Future UI Consumption

The Frontend/Admin Panel is expected to:
1.  Call an API endpoint that uses `AdminActivityQueryInterface`.
2.  Receive a JSON list of `AdminActivityDTO`.
3.  Use the `action`, `targetType`, and `changes` fields to construct a localized, human-readable sentence.
    *   *Example:* If `action` is `'update'` and `targetType` is `'admin'`, the UI renders "Updated Admin details".

This keeps the Backend generic and the UI flexible.
