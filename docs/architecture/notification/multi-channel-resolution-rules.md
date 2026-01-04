# Multi-Channel Resolution Rules

**Status:** LOCKED
**Phase:** 10.3
**Layer:** Domain

This document defines the policy for resolving multiple channels for a single notification.

## 1. Single vs. Multiple Channels

The routing system supports resolving one or more channels for a single notification event.

- **Single Channel:** Common for standard alerts (e.g., just Email).
- **Multiple Channels:** Common for high-priority or critical alerts (e.g., Email AND Telegram).

The `resolveChannels` method (and `resolvePreference`) returns an `array` of `NotificationChannelType`. This array size MAY be 0, 1, or >1.

## 2. Meaning of Ordering

The order of channels in the resolved list is **meaningful**.

- **Primary Intent:** The first element is considered the primary preferred channel.
- **Secondary Intent:** Subsequent elements are considered secondary preferences.
- **Resolver Defines Intent:** The resolver provides the channels in the *preferred* order.
- **No Execution Semantics:** This document does **NOT** define whether the dispatcher executes them sequentially, in parallel, or stops on success. That is an execution detail (Phase 11). The resolver only provides the *ordered list of preferred targets*.

## 3. No Fan-Out Execution

- The **Routing** layer (Resolver) defines *intent*.
- The **Dispatcher** layer handles *execution*.
- The Resolver does NOT "fork" the process. It simply returns a list.
- **Constraint:** The Resolver must not attempt to trigger parallel processes.

## 4. Policy Examples

| Severity | Default Policy | Logic |
| :--- | :--- | :--- |
| **CRITICAL** | `[EMAIL, TELEGRAM]` | Redundancy required. |
| **WARNING** | `[EMAIL]` | Standard delivery. |
| **INFO** | `[EMAIL]` | Standard delivery. |

**Note:** These are examples. Actual policies are defined by the data in System Defaults and Admin Preferences.

## 5. Constraint on "No Channel"

- It is explicitly allowed to resolve to **ZERO** channels.
- **Use Case:** Admin has muted a specific notification type.
- **Handling:** The system must gracefully handle an empty list (Result: `isNoChannelAvailable = true`) without logging an error as a failure. It is a "Success: Skipped" state.
