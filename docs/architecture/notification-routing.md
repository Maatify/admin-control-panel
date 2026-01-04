# Notification Routing Architecture (Phase 10)

## Phase Responsibilities

* **Phase 8 (Notification Intent)**: Determines *why* a notification should be sent (e.g., "Account Created", "Payment Failed"). It generates the intent but does not decide *how* to deliver it.
* **Phase 10 (Channel Routing)**: Determines *which* channels should be used for delivery (e.g., Email, Telegram, Webhook). This is a **pure decision layer**. It filters available channels based on user preferences and system configuration.
* **Phase 9 (Delivery Execution)**: Handles the physical transmission of the message via the selected channels. It executes the delivery logic using adapters.

## Mandatory Explicit Statements

1.  **Phase 10 MUST NOT send notifications**: The routing layer is strictly for decision-making. It must never initiate network calls or invoke delivery adapters.
2.  **Phase 10 MUST NOT call delivery adapters**: Senders and adapters are concerns of the Delivery layer (Phase 9), not Routing.
3.  **Phase 10 MUST NOT persist delivery state**: It does not track success/failure or delivery attempts. It only returns a list of selected channels.
4.  **Phase 10 is a pure decision layer**: Its sole output is a list of channels (`NotificationChannelType[]`).
5.  **Routing ≠ Delivery**: Deciding "Email" is distinct from "Sending an Email".
6.  **Routing ≠ Intent**: Deciding "Email" is distinct from "User Requested Password Reset".

## Contracts

The strict contract for routing is defined in `App\Domain\Contracts\NotificationRoutingInterface`.
All routing logic must implement this interface and adhere to the "decision-only" principle.
