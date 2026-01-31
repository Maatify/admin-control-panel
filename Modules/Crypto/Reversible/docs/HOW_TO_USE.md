# 🔐 How to Use — Reversible Crypto Module

> This example shows **real usage** without Email / Queue coupling.
> Same flow will be used later in Email Queue, Webhooks, Jobs, etc.

---

## 1️⃣ Bootstrap (One-time setup)

📍 **Recommended location:**

For example, place it in:

```text
bootstrap/crypto.php
```

or inside a **Service Provider** / application bootstrap layer responsible for wiring services and dependencies.

```php
use Maatify\Crypto\Reversible\Algorithms\Aes256GcmAlgorithm;use Maatify\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;use Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;use Maatify\Crypto\Reversible\ReversibleCryptoService;

// 1. Register allowed algorithms
$registry = new ReversibleCryptoAlgorithmRegistry();
$registry->register(new Aes256GcmAlgorithm());

// 2. Load keys (example only – real source is env / vault / secrets manager)
$keys = [
    'v1' => hex2bin('00112233445566778899aabbccddeeff00112233445566778899aabbccddeeff'),
];

// 3. Define active key + algorithm
$cryptoService = new ReversibleCryptoService(
    registry: $registry,
    keys: $keys,
    activeKeyId: 'v1',
    activeAlgorithm: ReversibleCryptoAlgorithmEnum::AES_256_GCM
);
```

📌 **Important**

* Keys MUST be raw binary (32 bytes for AES-256)
* Rotation is done by changing `activeKeyId`

---

## 2️⃣ Encrypt Data

```php
$plainText = 'Sensitive message content';

$encrypted = $cryptoService->encrypt($plainText);
```

### 🔎 Returned structure

```php
[
    'cipher'    => string, // encrypted binary data
    'algorithm' => ReversibleCryptoAlgorithmEnum,
    'key_id'    => string,
    'metadata'  => ReversibleCryptoMetadataDTO {
        iv:  ?string,
        tag: ?string
    }
]
```

### 🗄️ What you MUST store

| Field     | Required      |
|-----------|---------------|
| cipher    | ✔️            |
| algorithm | ✔️            |
| key_id    | ✔️            |
| iv        | ✔️ (for AEAD) |
| tag       | ✔️ (for AEAD) |

---

## 3️⃣ Decrypt Data

```php
use Maatify\Crypto\Reversible\DTO\ReversibleCryptoMetadataDTO;

// rebuild metadata from storage
$metadata = new ReversibleCryptoMetadataDTO(
    iv:  $storedIv,
    tag: $storedTag
);

$plainText = $cryptoService->decrypt(
    cipher: $storedCipher,
    keyId: $storedKeyId,
    algorithm: $storedAlgorithm,
    metadata: $metadata
);
```

✔️ If anything is wrong → **exception is thrown**
❌ No silent failure
❌ No partial success

---

## 4️⃣ Key Rotation (Example)

```php
$keys = [
    'v1' => $oldKey,
    'v2' => $newKey,
];

$cryptoService = new ReversibleCryptoService(
    $registry,
    $keys,
    activeKeyId: 'v2', // NEW key
    activeAlgorithm: ReversibleCryptoAlgorithmEnum::AES_256_GCM
);
```

📌 Result:

* New data encrypted with `v2`
* Old data still decryptable using stored `key_id`

---

## 5️⃣ Error Handling

All errors are **fail-closed**:

```php
try {
    $plain = $cryptoService->decrypt(...);
} catch (\Throwable $e) {
    // log + abort
}
```

Typical failures:

* Unsupported algorithm
* Missing key
* Invalid tag
* Corrupted cipher
* Authentication failure

---

## 6️⃣ What NOT to do ❌

```php
// ❌ Do NOT use for passwords
// ❌ Do NOT hash passwords here
// ❌ Do NOT ignore metadata
// ❌ Do NOT derive cipher from enum
// ❌ Do NOT store cipher without algorithm + key_id
```

---

## 7️⃣ Where this is used next

This exact flow will be reused in:

* 📬 Email Queue (encrypt body / metadata)
* 🌐 Webhook payloads
* ⏳ Deferred jobs
* 🗄️ Secure recoverable storage

---

## ✅ Summary

✔️ Explicit
✔️ Deterministic
✔️ Fail-closed
✔️ Library-grade
✔️ Rotation-ready
✔️ No magic

---
