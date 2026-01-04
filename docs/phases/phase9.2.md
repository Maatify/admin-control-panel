# Phase 9.2: Notification Channel Adapters

## Overview
This phase implements the concrete adapters for delivering notifications via specific channels. These adapters are responsible for the physical delivery mechanism (e.g., SMTP, HTTP API) but do not handle orchestration, retries, or routing logic.

## Adapters
Adapters are Infrastructure-layer classes that implement `App\Domain\Contracts\NotificationSenderInterface`.

### Implemented Adapters
1.  **EmailNotificationSender** (`App\Infrastructure\Notification\EmailNotificationSender`)
    -   **Channel:** `email`
    -   **Behavior:** Simulates sending an email. Validates that the recipient address is not empty. Returns a `DeliveryResultDTO` indicating success or failure. This implementation is currently a stub and does not integrate with a real mailer.

2.  **FakeNotificationSender** (`App\Infrastructure\Notification\FakeNotificationSender`)
    -   **Channel:** `fake`, `test`
    -   **Behavior:** Always returns a successful `DeliveryResultDTO`. Useful for testing and development environments where actual delivery is not desired.

## Why Orchestration is Deferred (Phase 9.3+)
Orchestration involves selecting the correct adapter based on the notification intent, handling failures with retries, and potentially managing queues.
-   **Separation of Concerns:** Adapters should only know *how* to send, not *when* or *if* to send.
-   **Complexity Management:** Implementing adapters first allows verifying the delivery contracts before adding the complexity of a dispatcher or manager.
-   **Testability:** Simple adapters are easier to unit test. The orchestrator can then be tested using the `FakeNotificationSender` or mocks.

## What Adapters Are NOT
-   **Routers:** They do not decide which channel to use.
-   **Managers:** They do not handle business logic or preferences.
-   **Loggers:** They do not log to the audit or security logs (though they return result objects that can be logged by the caller).
