# Key Rotation

The `KeyRotation` module manages the lifecycle and states of root cryptographic keys. It is designed to allow seamless updates to encryption keys without causing downtime or rendering legacy data unreadable.

## Core Concepts

The system relies on a collection of keys, each identified by a unique ID and assigned a specific state.

### Key States

1.  **ACTIVE:** There can be **only one** ACTIVE key at any given time. This key is used for all *new* encryption operations. It can also be used for decryption.
2.  **INACTIVE:** These keys are no longer used for new encryption, but they remain valid and available for *decrypting* previously encrypted data.
3.  **RETIRED:** These keys have been explicitly revoked. The system will refuse to decrypt data associated with a RETIRED key. This is useful for responding to key compromises.

## The Process

When you encrypt data using the DX layer (`CryptoProvider`), the payload includes the ID of the key used (the currently ACTIVE key).

When you later attempt to decrypt that data, the `KeyRotationService` uses that stored ID to fetch the corresponding key from the provided `KeyProviderInterface`, regardless of whether it is currently ACTIVE or INACTIVE.

## Core Components

-   **`KeyRotationService`**: The primary service managing keys.
-   **`KeyProviderInterface`**: A contract the host application must implement to supply keys to the module (e.g., from a database, config file, or KMS).
-   **`KeyStatusEnum`**: Defines the valid states (ACTIVE, INACTIVE, RETIRED).

## Usage Requirements

To use the KeyRotation module, the host application **must** implement the `KeyProviderInterface`.

```php
interface KeyProviderInterface
{
    /**
     * Return the currently active key for new encryptions.
     */
    public function getActiveKey(): CryptoKeyDTO;

    /**
     * Retrieve a specific key by its ID for decryption.
     */
    public function getKeyById(string $id): ?CryptoKeyDTO;
}
```

## Security Rules Enforced

-   The module will throw an exception if multiple ACTIVE keys are detected.
-   The module will throw an exception if an encryption operation is attempted without an ACTIVE key.
-   The module will throw an exception if a decryption operation attempts to use a RETIRED key.
