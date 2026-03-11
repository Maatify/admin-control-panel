# Chapter 7: Repository Layer

The `Maatify\Verification` module relies entirely on abstractions for data persistence. This ensures that the core domain logic (generation, validation, anti-brute-force rules) is completely decoupled from the specific database technology (e.g., MySQL, PostgreSQL, Redis) or the framework's ORM (e.g., Eloquent, Doctrine).

## The Contract: `VerificationCodeRepositoryInterface`

Every integration must provide an implementation of this interface.

```php
interface VerificationCodeRepositoryInterface
{
    public function store(VerificationCode $code): void;
    public function findActive(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose): ?VerificationCode;
    public function findByCodeHash(string $codeHash): ?VerificationCode;
    public function incrementAttempts(int $codeId): void;
    public function markUsed(int $codeId, ?string $usedIp = null): void;
    public function expire(int $codeId): void;
    public function expireAllFor(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose): void;
}
```

This contract explicitly defines *what* operations are required, without dictating *how* they are executed:

1.  **Creation (`store`)**: Saves a new `VerificationCode` DTO.
2.  **Retrieval (`findActive`, `findByCodeHash`)**: Fetches a code based on active status, identity, purpose, or its SHA-256 hash.
3.  **State Modification (`incrementAttempts`, `markUsed`, `expire`, `expireAllFor`)**: These methods encapsulate state transitions directly. They do not require fetching the entity, modifying it, and saving it back (which can lead to race conditions). Instead, they imply atomic updates.

## The Default Implementation: `PdoVerificationCodeRepository`

The module provides a default implementation using PHP Data Objects (PDO), suitable for any relational database (MySQL, PostgreSQL, SQLite).

```php
class PdoVerificationCodeRepository implements VerificationCodeRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private ClockInterface $clock
    ) {}
}
```

### Table Schema Requirements

If you use the `PdoVerificationCodeRepository`, your database must contain a table named `verification_codes` with the following approximate schema:

```sql
CREATE TABLE `verification_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identity_type` varchar(50) NOT NULL,
  `identity_id` varchar(255) NOT NULL,
  `purpose` varchar(50) NOT NULL,
  `code_hash` varchar(64) NOT NULL,
  `status` enum('active','used','expired') NOT NULL DEFAULT 'active',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `created_ip` varchar(45) DEFAULT NULL,
  `used_ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_active_lookup` (`identity_type`, `identity_id`, `purpose`, `status`),
  KEY `idx_code_hash` (`code_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

*Note: Indexes are crucial for the `findActive` and `findByCodeHash` operations to remain performant under load.*

### Atomic Updates in `PdoVerificationCodeRepository`

The repository handles state changes using direct `UPDATE` queries. This is critical for preventing race conditions (e.g., if multiple requests try to validate the same code simultaneously).

```php
public function incrementAttempts(int $codeId): void
{
    $stmt = $this->pdo->prepare("
        UPDATE verification_codes
        SET attempts = attempts + 1
        WHERE id = :id
    ");
    $stmt->execute(['id' => $codeId]);
}
```

By executing `attempts = attempts + 1` atomically in the database, the repository ensures that concurrent validation failures will accurately increment the counter, triggering the expiration logic correctly when `maxAttempts` is reached.

## Replacing the Repository

If your application uses a different storage mechanism (e.g., Redis for faster lookups or Eloquent for tighter framework integration), you simply create a new class implementing `VerificationCodeRepositoryInterface` and bind it in your Dependency Injection container. The domain services (`VerificationCodeGenerator`, `VerificationCodeValidator`) will automatically use the new implementation without any code changes.