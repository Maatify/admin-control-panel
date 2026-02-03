# How To Use: Cryptography Module

## 1. Intended Audience

This guide is intended for:
- **Backend Developers** integrating encryption or password hashing into applications.
- **Infrastructure Engineers** configuring the runtime environment and secrets.
- **Security Reviewers** auditing the usage and boundaries of cryptographic operations.

## 2. High-Level Usage

Consumers are expected to interact with this module primarily through the **DX Layer** (`CryptoProvider`).
The underlying modules (`KeyRotation`, `HKDF`, `Reversible`) are designed to be composed together, but direct usage is reserved for advanced custom integrations.

Cryptographic services are **consumed**, not extended. You should not subclass the core services or attempt to override their behavior.

## 3. Password Hashing Usage

The Password pipeline is strictly for one-way hashing of authentication credentials.

**Conceptual Flow:**
1. Retrieve the `PasswordService` via Dependency Injection.
2. **Hash** a plain text password for storage.
3. **Verify** a plain text password against a stored hash.
4. **Check** if a stored hash needs rehashing (e.g., after policy updates).

```php
// 1. Hash a new password
$hash = $passwordService->hash($inputPassword);
// Store $hash in your database (VARCHAR/TEXT)

// 2. Verify a login attempt
$isValid = $passwordService->verify($inputPassword, $storedHash);

// 3. Maintenance (Login Flow)
if ($isValid && $passwordService->needsRehash($storedHash)) {
    $newHash = $passwordService->hash($inputPassword);
    // Update storage with $newHash
}
```

**Constraints:**
- Never encrypt passwords. Always hash them.
- Do not make assumptions about the hash format (it is an opaque string).

## 4. Context-Based Encryption Usage (HKDF)

This is the **standard** method for encrypting data. It ensures that different parts of your application use different encryption keys, derived from your master root keys.

**Context Strings:**
You must define explicit, versioned context strings.
Examples: `user:email:v1`, `payment:card:v1`, `audit:log:v2`.

**Why HKDF?**
If the `user:email:v1` key is compromised, the `payment:card:v1` data remains secure.

**Usage:**

```php
// 1. Obtain an encrypter for a specific context
$encrypter = $cryptoProvider->context('user:phone_number:v1');

// 2. Encrypt data (Returns an opaque object/DTO)
$encryptedResult = $encrypter->encrypt($sensitiveData);

// 3. Decrypt data
// The $encryptedResult contains all necessary metadata (IV, Key ID, Tag).
$plainText = $encrypter->decrypt($encryptedResult);
```

**Note:** The result of encryption is an object/array containing the ciphertext, IV, tag, and key ID. You must store all these components to decrypt successfully.

## 5. Direct Encryption Usage

**⚠️ ADVANCED USE ONLY**

Direct encryption uses the Root Keys directly without HKDF derivation. This bypasses domain separation.

**Risks:**
- A key compromise affects ALL data encrypted with that key.
- No granular isolation between features.

**Acceptable Use Cases:**
- System-internal blobs where context is impossible to define.
- Interop with legacy systems (if strictly necessary).

```php
$encrypter = $cryptoProvider->direct();
$encryptedResult = $encrypter->encrypt($data);
```

## 6. DX Layer Usage

The `CryptoProvider` is an optional facade. It exists to prevent "wiring fatigue" and to ensure that HKDF is correctly applied in the standard pipeline.

If you are extracting this library and do not wish to use the DX layer, you must manually wire:
`KeyRotationService` → `HKDFService` → `ReversibleCryptoAlgorithmRegistry` → `ReversibleCryptoService`.

## 7. Environment Configuration

The Cryptography Module is **stateless** and **environment-agnostic**. It does **not** know how to load secrets. It relies entirely on the host application to provide them via Dependency Injection (DTOs or Providers).

### Responsibility

- **Host Application:** Responsible for reading environment variables (`.env`, `$_ENV`, `getenv()`) and passing them to the module.
- **Crypto Module:** Receives secrets as PHP strings/objects. It never reads the environment directly.

### Secrets Required

You typically need to provide:
1. **Root Keys:** A JSON or array structure containing versioned keys for `KeyRotation`.
2. **Password Pepper:** A high-entropy random string for the `Password` module.

### Access Patterns (Conceptual)

The host application is free to use `$_ENV`, `getenv()`, or any configuration loader.

**Example: Reading from $_ENV**
```php
// In your App's Bootstrap or Config Service
$rootKeysJson = $_ENV['APP_ROOT_KEYS'] ?? null;
$passwordPepper = $_ENV['APP_PASSWORD_PEPPER'] ?? null;

if (!$rootKeysJson || !$passwordPepper) {
    throw new RuntimeException("Critical security configuration missing.");
}
```

**Example: Reading via getenv()**
```php
$rootKeysJson = getenv('APP_ROOT_KEYS');
$passwordPepper = getenv('APP_PASSWORD_PEPPER');
```

### Critical Rules

1. **Fail-Closed:** If secrets are missing or empty, the application MUST halt. The Crypto module will throw exceptions if initialized with empty keys.
2. **Treat as Secrets:** These values are the keys to your kingdom. Never log them. Never expose them in debug dumps.
3. **No Defaults:** There are **NO** default keys. The module does not fallback to "development" keys.
4. **Immutable:** The Crypto module does not modify these values. It uses them in memory only.

### Build-Time vs. Runtime

- **Build-Time:** Configuration (e.g., choosing `AES-256-GCM` vs `ChaCha20`) can be hardcoded or config-driven.
- **Runtime:** Secrets (Keys, Peppers) must be injected at runtime. They should never be committed to source control.

**Summary:** The Crypto Module is a passive consumer of secrets. You must feed it securely.
