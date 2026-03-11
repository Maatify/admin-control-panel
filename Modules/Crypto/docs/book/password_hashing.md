# Password Hashing

The `Password` module provides a secure, modern implementation for hashing and verifying passwords.

## Core Components

-   **`PasswordHasherInterface`**: The contract defining the hashing operations (`hash`, `verify`, `needsRehash`).
-   **`PasswordHasher`**: The concrete implementation.
-   **`PasswordPepperProviderInterface`**: An interface for injecting a global secret (pepper) into the hashing process.

## The Hashing Pipeline

The module employs a two-step hashing process:

1.  **Peppering (HMAC-SHA256):** If a `PasswordPepperProviderInterface` is available, the plaintext password is first hashed using HMAC-SHA256 with the secret pepper as the key. This step provides significant protection against offline dictionary attacks if the database is compromised but the application secret is not.
2.  **Hashing (Argon2id):** The result of the pepper step (or the raw plaintext if no pepper is configured) is then hashed using Argon2id, a memory-hard hashing algorithm resistant to GPU cracking.

## Usage

```php
use Maatify\Crypto\Password\PasswordHasherInterface;

// Inject the interface into your service
public function __construct(private PasswordHasherInterface $hasher) {}

// Hashing a new password
$hash = $this->hasher->hash($plaintextPassword);

// Verifying a password during login
if ($this->hasher->verify($plaintextPassword, $storedHash)) {
    // Login successful

    // Check if the hash needs upgrading (e.g., algorithm parameters changed)
    if ($this->hasher->needsRehash($storedHash)) {
        $newHash = $this->hasher->hash($plaintextPassword);
        // Update the stored hash in the database
    }
}
```

## Security Considerations

-   **Always use a Pepper:** It is highly recommended to provide a `PasswordPepperProviderInterface`. The pepper must be a strong, randomly generated secret stored securely (e.g., environment variable, secrets manager) and *never* in the same database as the hashes.
-   **Rehashing:** Always implement the `needsRehash` check during successful logins to ensure your hashes stay up-to-date with current security recommendations.
