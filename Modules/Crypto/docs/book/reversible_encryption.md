# Reversible Encryption

The `Reversible` module provides symmetric Authenticated Encryption with Associated Data (AEAD). This ensures both the confidentiality and integrity of the encrypted data.

## Core Components

-   **`ReversibleCryptoAlgorithmInterface`**: Defines the contract for encryption algorithms.
-   **`ReversibleCryptoService`**: Orchestrates the encryption and decryption processes.
-   **`ReversibleCryptoAlgorithmEnum`**: Defines supported algorithms.

## Supported Algorithms

The module strictly limits supported algorithms to strong, authenticated ciphers. Currently, the primary recommendation and default is:

-   **XChaCha20-Poly1305 (Sodium):** Provided via `SodiumAeadXchacha20poly1305Ietf`. This is a modern, highly secure, and fast AEAD algorithm.

## AEAD Guarantees

Authenticated Encryption ensures that:

1.  **Confidentiality:** The data cannot be read without the correct key.
2.  **Integrity:** The data cannot be modified without detection. The decryption process will fail immediately if the ciphertext or the authentication tag has been tampered with.

## Direct Usage vs. Context Usage

While the `ReversibleCryptoService` can be used directly, **this is strongly discouraged**. Direct usage bypasses the domain separation provided by HKDF and the key management provided by KeyRotation.

**Always prefer using the `CryptoProvider` (DX Layer) for encryption tasks.**

## Example (Conceptual Direct Usage)

*Note: You should almost always use `CryptoProvider->context(...)` instead.*

```php
use Maatify\Crypto\Reversible\ReversibleCryptoService;
use Maatify\Crypto\DTO\CryptoKeyDTO;

// (Assuming $service and $key are properly instantiated)

$plaintext = "Sensitive Data";

// Encryption returns a DTO containing the Ciphertext, IV, Tag, etc.
$encryptedPayload = $service->encrypt($plaintext, $key);

// Decryption requires the full payload and the correct key
$decryptedText = $service->decrypt($encryptedPayload, $key);
```

## Fail-Closed Behavior

The `ReversibleCryptoService` will throw exceptions (e.g., `CryptoException`) under any of the following conditions:

-   The key is invalid or missing.
-   The authentication tag does not match (tampering detected).
-   The ciphertext is malformed.
-   The required algorithm is not supported by the environment.
