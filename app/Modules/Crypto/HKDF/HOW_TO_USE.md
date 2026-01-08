# HKDF Module — How To Use

This document explains the **correct and approved usage**
of the HKDF module within the Admin Control Panel architecture.

This is a **consumer-facing guide**.
Architectural intent and design rationale are documented in `README.md`
and `ADR-003.md`.

---

## Prerequisites

Before using HKDF, the following MUST already be in place:

- A valid **active root key** obtained from the Key Rotation module
- A **stable, explicit, versioned context string**
- A clear understanding of the cryptographic consumer (email, sms, totp, etc.)

HKDF MUST NOT be used in isolation.

---

## Basic Usage Flow

The approved usage flow is:

```text
KeyRotation
   ↓
ACTIVE_ROOT_KEY
   ↓
HKDFService
   ↓
Derived Key
   ↓
Crypto Primitive (AES-GCM, HMAC, etc.)
````

HKDF is responsible **only** for key derivation.

---

## Step 1 — Obtain the Root Key

HKDF NEVER generates or stores root keys.

A root key MUST come from the Key Rotation module:

```php
$rootKey = $keyRotationService->getActiveKey();
```

The root key is treated as **binary secret material**.

---

## Step 2 — Define a Context

Every cryptographic use-case MUST define its own context.

Contexts MUST be:

* Explicit
* Constant
* Versioned
* Not user-controlled

### Example

```php
use App\Modules\Crypto\HKDF\HKDFContext;

$context = new HKDFContext('notification:email:v1');
```

Contexts SHOULD be defined as constants in the consuming module,
not inline strings scattered across the codebase.

---

## Step 3 — Derive a Key

Use `HKDFService` as the single entry point.

```php
use App\Modules\Crypto\HKDF\HKDFService;

$hkdf = new HKDFService();

$derivedKey = $hkdf->deriveKey(
    $rootKey,
    $context,
    32 // desired key length in bytes
);
```

The returned key is a **binary string** suitable for direct use
with cryptographic primitives.

---

## Step 4 — Use the Derived Key

The derived key can now be used with cryptographic primitives:

* AES-256-GCM
* HMAC-SHA256
* Any other symmetric construction

HKDF does NOT perform encryption or hashing itself.

---

## Context Versioning Strategy

Contexts MUST be versioned to preserve backward compatibility.

### Example

```text
notification:email:v1
notification:email:v2
```

When a context version changes:

* Old data remains decryptable using the old context
* New data is encrypted using the new context
* No automatic re-encryption is performed

Versioning is the ONLY approved mechanism for context evolution.

---

## Approved Usage Examples

| Use Case                      | Context                    |
| ----------------------------- | -------------------------- |
| Email notification encryption | `notification:email:v1`    |
| SMS payload encryption        | `notification:sms:v1`      |
| Telegram payload encryption   | `notification:telegram:v1` |
| Notification queue payload    | `notification:payload:v1`  |
| TOTP secret storage (at rest) | `totp:storage:v1`          |
| Exported files                | `export:file:v1`           |

Each context MUST map to exactly one cryptographic responsibility.

---

## Common Mistakes (DO NOT DO THIS)

### ❌ Using user input as context

```php
new HKDFContext($userInput); // FORBIDDEN
```

Contexts must never be dynamic.

---

### ❌ Reusing the same context across multiple purposes

```text
encryption:v1
```

Too generic. This defeats domain separation.

---

### ❌ Using HKDF as a replacement for key rotation

HKDF does not manage key lifecycle.
Rotation MUST be handled by the Key Rotation module.

---

### ❌ Storing derived keys

Derived keys MUST be treated as **ephemeral runtime material**.

They MUST NOT be persisted.

---

## Error Handling

HKDF follows a **fail-closed** design.

Any invariant violation results in an exception:

* Invalid root key
* Invalid output length
* Invalid context

Consumers MUST NOT attempt to recover silently.

---

## Security Notes

* HKDF does not increase entropy
* HKDF isolates entropy usage
* Security depends on the root key quality
* HKDF must always be used with explicit intent

Misuse of HKDF is considered a **security defect**, not a feature bug.

---

## Final Reminder

HKDF is an **organizational cryptographic layer**.

Use it to:

* Separate domains
* Reduce blast radius
* Support long-lived systems

Do NOT use it to:

* Hide design flaws
* Avoid key rotation
* Add unnecessary complexity

Follow the rules.
