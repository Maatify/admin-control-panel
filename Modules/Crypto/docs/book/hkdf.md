# HKDF (HMAC-based Extract-and-Expand Key Derivation Function)

The `HKDF` module provides cryptographically secure key derivation based on RFC 5869. This is a critical component for ensuring **Domain Separation**.

## Why Domain Separation?

If you use the same encryption key for different purposes (e.g., encrypting email addresses and encrypting payment tokens), a vulnerability or key leak in one area compromises the other.

HKDF solves this by taking a single "Root Key" and a "Context String" to derive a unique, separate key for that specific context.

## Core Components

-   **`HKDFService`**: The service responsible for performing the derivation.

## Context Strings

A context string is a unique identifier for a specific cryptographic domain.

**Best Practices for Context Strings:**

-   **Be Specific:** e.g., `user:email_address`, `system:api_token`.
-   **Include Versioning:** e.g., `user:email_address:v1`. This allows you to rotate the context itself if needed in the future.
-   **Never Change Them:** Once data is encrypted using a specific context string, changing that string will render the data permanently undecryptable.

## The Process

The HKDF process involves two steps (handled internally by `HKDFService`):

1.  **Extract:** Creates a fixed-length pseudorandom key (PRK) from the input key material.
2.  **Expand:** Expands the PRK into the desired length of output key material using the specific Context String.

## Usage (Internal)

Normally, you do not interact with `HKDFService` directly. It is orchestrated by the `CryptoContextFactory` in the DX layer.

However, conceptually, it works like this:

```php
// $rootKey is a CryptoKeyDTO managed by KeyRotation
// $context is 'user:email:v1'

$derivedKeyMaterial = $hkdfService->deriveKey(
    $rootKey->getKeyMaterial(),
    $context,
    32 // Desired key length in bytes (e.g., for XChaCha20)
);

// The $derivedKeyMaterial is then used by the ReversibleCryptoService
```
