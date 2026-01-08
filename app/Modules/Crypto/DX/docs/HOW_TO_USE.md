# How To Use: Crypto DX Layer

The `CryptoProvider` is the primary entry point for all cryptographic operations in the application layer.
It simplifies the usage of the underlying strictly separated crypto modules.

---

## 1. Injection

Inject `App\Modules\Crypto\DX\CryptoProvider` into your service or controller.

```php
use App\Modules\Crypto\DX\CryptoProvider;

class MyService
{
    public function __construct(
        private CryptoProvider $crypto
    ) {}
}
```

---

## 2. Context-Based Encryption (Recommended)

Use this pipeline for domain-separated data (e.g., PI, Tokens, Notification payloads).
It automatically derives unique keys for the specific context from the active root keys using HKDF.

**Pipeline:** `KeyRotation` → `HKDF` → `Reversible`

```php
// 1. Get the encrypter for your specific context
// Context string MUST be versioned and explicit.
$encrypter = $this->crypto->context('notification:email:v1');

// 2. Encrypt
$encrypted = $encrypter->encrypt('secret payload');
// Result: ['cipher' => ..., 'algorithm' => ..., 'key_id' => ..., 'metadata' => ...]

// 3. Decrypt
// The encrypter automatically handles key lookup by ID.
$plaintext = $encrypter->decrypt(
    $encrypted['cipher'],
    $encrypted['key_id'],
    $encrypted['algorithm'],
    $encrypted['metadata']
);
```

---

## 3. Direct Encryption (Use with Caution)

Use this pipeline only when HKDF derivation is explicitly not required (e.g., legacy data or specific system internals).
It uses the raw root keys directly.

**Pipeline:** `KeyRotation` → `Reversible`

```php
// 1. Get the direct encrypter
$encrypter = $this->crypto->direct();

// 2. Encrypt/Decrypt (Same API as above)
$encrypted = $encrypter->encrypt('raw secret');
```

---

## 4. Password Hashing

Provides direct access to the `PasswordService` for hashing and verification.
This pipeline is isolated from the encryption keys.

**Pipeline:** `HMAC(Pepper)` → `Argon2id`

```php
$passwordService = $this->crypto->password();

// Hash
$hash = $passwordService->hash('user-password');

// Verify
$isValid = $passwordService->verify('user-password', $hash);
```

---

## Summary of Methods

| Method | Returns | Use Case |
| :--- | :--- | :--- |
| `context(string $ctx)` | `ReversibleCryptoService` | **Default**. Encrypting data with domain separation. |
| `direct()` | `ReversibleCryptoService` | **Advanced**. Encrypting data with raw root keys. |
| `password()` | `PasswordService` | Hashing and verifying passwords. |
