# 🔁 Key Rotation — How To Use

This guide explains **how to use the KeyRotation module**
to manage cryptographic keys and safely integrate it with
the ReversibleCrypto module.

---

## 1️⃣ When Do You Need Key Rotation?

You need this module if:

- You encrypt data that must be decrypted later
- You store encrypted data long-term (email queue, jobs, webhooks)
- You want to rotate keys without breaking old data
- You need exactly one active encryption key

If you only hash passwords → ❌ DO NOT use this module.

---

## 2️⃣ Define Cryptographic Keys

Each key is represented as an immutable object.

```php
use Maatify\Crypto\KeyRotation\DTO\CryptoKeyDTO;use Maatify\Crypto\KeyRotation\KeyStatusEnum;

$key = new CryptoKeyDTO(
    id: 'v1',
    material: random_bytes(32), // 256-bit binary key
    status: KeyStatusEnum::ACTIVE,
    createdAt: new DateTimeImmutable()
);
````

⚠️ The key material:

* Must be raw binary
* Must NEVER be logged
* Must be stored securely (env / DB / vault)

---

## 3️⃣ Create a Key Provider

### Example: In-Memory Provider (bootstrap / tests)

```php
use Maatify\Crypto\KeyRotation\Providers\InMemoryKeyProvider;

$provider = new InMemoryKeyProvider([
    $key1,
    $key2,
]);
```

Rules enforced automatically:

* Exactly ONE ACTIVE key
* Zero or multiple ACTIVE keys → hard failure

---

## 4️⃣ Choose a Rotation Policy

Currently supported policy:

```php
use Maatify\Crypto\KeyRotation\Policy\StrictSingleActiveKeyPolicy;

$policy = new StrictSingleActiveKeyPolicy();
```

This policy enforces:

* Only ACTIVE key can encrypt
* INACTIVE and RETIRED keys may decrypt
* No silent fallback

---

## 5️⃣ Create the KeyRotationService

```php
use Maatify\Crypto\KeyRotation\KeyRotationService;

$rotation = new KeyRotationService(
    provider: $provider,
    policy: $policy
);
```

This service:

* Orchestrates rotation
* Validates invariants
* Exports keys for crypto usage

---

## 6️⃣ Export Keys for Encryption / Decryption

The crypto module must NEVER access the provider directly.

```php
$config = $rotation->exportForCrypto();
```

Returned structure:

```php
[
    'active_key_id' => 'v1',
    'keys' => [
        'v1' => <binary key>,
        'v2' => <binary key>,
    ]
]
```

---

## 7️⃣ Integrate with ReversibleCrypto

```php
use Maatify\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;use Maatify\Crypto\Reversible\ReversibleCryptoService;

$crypto = new ReversibleCryptoService(
    registry: $registry,
    keys: $config['keys'],
    activeKeyId: $config['active_key_id'],
    activeAlgorithm: ReversibleCryptoAlgorithmEnum::AES_256_GCM
);
```

### Encrypt

```php
$result = $crypto->encrypt('secret payload');

$encrypted = $result->cipher;
$keyId     = $result->keyId;
$metadata  = $result->metadata;
```

### Decrypt

```php
$plain = $crypto->decrypt(
    cipher: $encrypted,
    keyId: $keyId,
    metadata: $metadata
);
```

---

## 8️⃣ Rotate to a New Key

```php
$decision = $rotation->rotateTo('v2');
```

Effects:

* `v2` becomes ACTIVE
* Previous ACTIVE key becomes INACTIVE
* New encryption uses `v2`
* Old data remains decryptable

❌ No automatic re-encryption occurs.

---

## 9️⃣ Failure Scenarios (Expected)

| Scenario                    | Result    |
|-----------------------------|-----------|
| No ACTIVE key               | Exception |
| Multiple ACTIVE keys        | Exception |
| Unknown key_id              | Exception |
| Encrypt with non-ACTIVE key | Exception |

The system always **fails closed**.

---

## 🔒 Security Notes

* Never log key material
* Key IDs may be logged for audit
* Rotation actions should be restricted
* Re-encryption is intentionally out of scope

---

## 🧠 Design Reminder

> Cryptography must be deterministic.
> Key rotation must be explicit.

If you need:

* Background re-encryption
* Key expiration policies
* Audit trails

Those require **separate modules and ADRs**.

---

## ✅ Summary

✔️ Safe key lifecycle
✔️ Explicit rotation
✔️ Backward-compatible decryption
✔️ Clean integration with crypto
✔️ Library-ready design

---

**End of How-To**
