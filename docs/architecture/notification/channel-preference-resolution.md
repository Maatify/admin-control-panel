# Channel Preference Resolution Semantics

**Status:** LOCKED
**Phase:** 10.2
**Layer:** Domain

This document explicitly defines how notification channels are resolved for a given admin and notification type. These rules are binding for any implementation of `NotificationChannelPreferenceResolverInterface`.

## 1. Resolution Priority Order

The resolver MUST follow this strict priority order:

1.  **Admin Preference:** If the admin has explicitly enabled/disabled channels for the specific notification type, this preference MUST be respected.
2.  **System Default:** If the admin has no explicit preference, the system default configuration for that notification type MUST be used.
3.  **None:** If no system default exists and no admin preference is found, NO channels are resolved.

## 2. Decision Logic

### 2.1. Admin Preference (Highest Priority)
- **Enabled:** If an admin has enabled a channel, it is included in the resolved list.
- **Disabled:** If an admin has disabled a channel, it MUST be excluded, even if it is a system default.

### 2.2. System Default (Fallback)
- Applied only when no specific admin preference record exists for the (admin, type) pair.
- Defaults define the baseline channels (e.g., Critical alerts default to Email + Telegram).

### 2.3. No Channel Available
- If the resolution process results in an empty list of channels, the `isNoChannelAvailable` flag in `ChannelResolutionResultDTO` MUST be set to `true`.
- This is a valid state (e.g., user disabled all notifications) and MUST NOT cause an exception.

## 3. Preference Only Responsibility

- **Explicit Rule:** The resolver is responsible for **PREFERENCE ONLY**.
- The resolver MUST return what the user *wants*, regardless of technical capability.
- **Availability Ignored:** The resolver MUST NOT check if the system "can" send (e.g., has API keys, valid email format).
- **Execution Failures:** Availability checks and failure handling belong strictly to the Dispatcher/Delivery layers (Phase 9/11). They are **OUT OF SCOPE** for preference resolution.
- If a user prefers a channel that is technically broken, the resolver MUST still return it. The Dispatcher will then fail to deliver, which is correct behavior.

## 4. Determinism Guarantees

- **Input:** `NotificationRoutingContextDTO (adminId, notificationType)`
- **Output:** `ChannelResolutionResultDTO`
- The same input MUST always result in the same output given the same state of preferences.
- Randomization or load balancing logic is FORBIDDEN in the preference resolver.

## 5. Prohibited Actions (Routing MUST NEVER)

- **Execute Delivery:** The resolver MUST NOT send emails, telegrams, etc.
- **Side Effects:** The resolver MUST NOT write to the database (read-only).
- **Mutate State:** The resolver MUST NOT change preference records.
