# Notification Routing Architecture

## Overview
This document defines the architecture for Notification Routing (Phase 10), ensuring clear separation from Notification Intent (Phase 8) and Notification Delivery (Phase 9).

## Phases

### Phase 8: Notification Intent
- Represents the "Request" or "Need" to send a notification.
- Responsible for identifying *what* happened (Event) and *who* might be interested.
- Does *not* decide *how* to send it.

### Phase 10: Channel Routing (Decision Layer)
- **Role:** Pure Decision Engine.
- **Responsibility:** Determines *which* channels should be used for a given Admin and Notification Type.
- **Inputs:** Admin ID, Notification Type.
- **Logic:** Intersection of:
  1. Admin's globally enabled channels (from `AdminNotificationChannelRepository`).
  2. Admin's preferences for the specific notification type (from `AdminNotificationPreferenceRepository`).
- **Output:** List of `NotificationChannelType`.
- **Constraints:**
  - **MUST NOT** send notifications.
  - **MUST NOT** call delivery adapters.
  - **MUST NOT** perform side effects (logging is allowed, but not business state changes).

### Phase 9: Delivery Execution
- **Role:** Execution Engine.
- **Responsibility:** Takes a specific Channel + Content + Recipient and delivers the message.
- **Components:** `NotificationDispatcher`, `NotificationSenderInterface` implementations (Email, Telegram, Webhook).
- **Relationship:** Phase 10 feeds Phase 9. The orchestration layer queries Phase 10 to get the list of channels, then invokes Phase 9 for each channel.

## Architecture Diagram (Conceptual)
[Intent] --> [Routing (Phase 10)] --> [List of Channels] --> [Dispatcher] --> [Delivery (Phase 9)]

## Contract
The routing contract is defined in `App\Domain\Contracts\NotificationRoutingInterface`.
