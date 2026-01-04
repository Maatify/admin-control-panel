# Notification Delivery Architecture

## Notification Intent vs Notification Delivery

### Notification Intent (Phase 8)
- Represents the **request** or **need** to notify an admin.
- Triggered by domain events or business logic.
- Stored/Logged as an intent (e.g. `NotificationMessageDTO`).
- Agnostic of the delivery mechanism (email, telegram, etc.) in terms of execution details.

### Notification Delivery (Phase 9)
- Represents the **execution** of sending the notification to a specific destination.
- Handles channel-specific logic (SMTP, API calls).
- Operates on a `NotificationDeliveryDTO` which contains all necessary data for delivery.
- Produces a `DeliveryResultDTO` to report success or failure without throwing exceptions for expected delivery errors.

## Phase 9.1: Contracts Only
This phase strictly defines the contracts and data transfer objects for the delivery mechanism. It does not implement any actual sending logic or infrastructure adapters.

The core components are:
- `NotificationDeliveryDTO`: Encapsulates what is being sent.
- `DeliveryResultDTO`: Encapsulates the outcome of the sending attempt.
- `NotificationSenderInterface`: Defines the contract for any service that can deliver notifications.
