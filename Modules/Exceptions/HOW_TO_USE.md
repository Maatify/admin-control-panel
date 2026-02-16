# How To Use Maatify/Exceptions

This guide provides step-by-step instructions for using the exceptions library effectively.

---

## 1️⃣ Throwing Exceptions

### System Exceptions
System exceptions represent critical failures (database offline, network error, filesystem unavailable).

```php
use Maatify\Exceptions\Exception\System\DatabaseConnectionMaatifyException;

// Throws a 503 Service Unavailable (SYSTEM)
throw new DatabaseConnectionMaatifyException('Unable to reach primary database');
```

### Validation Exceptions
Validation exceptions represent invalid input data provided by a client.

```php
use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;

// Throws a 400 Bad Request (VALIDATION)
throw new InvalidArgumentMaatifyException('Email field is required');
```

### Business Rule Exceptions
Business rule exceptions represent domain-specific violations (e.g., trying to activate a cancelled order).

```php
use Maatify\Exceptions\Exception\BusinessRule\BusinessRuleMaatifyException;

// Note: BusinessRuleMaatifyException is abstract. You must extend it.
class RefundException extends BusinessRuleMaatifyException {}

// Throws a 422 Unprocessable Entity (BUSINESS_RULE)
throw new RefundException('Cannot refund order #1234');
```

---

## 2️⃣ Using Overrides

Concrete exceptions allow overriding specific metadata if needed, but this is **strictly guarded**.

### HTTP Status Override

You can change the specific HTTP status code, but it **must remain within the same family** (e.g., 400 -> 404 is allowed, 400 -> 500 is forbidden).

```php
use Maatify\Exceptions\Exception\Validation\ValidationMaatifyException;
use Maatify\Exceptions\Enum\ErrorCodeEnum;

// Allowed: 400 (Bad Request) -> 422 (Unprocessable Entity) - Both are Client Errors
// Note: ValidationMaatifyException is abstract, so we extend it
class DetailedValidationException extends ValidationMaatifyException {}

throw new DetailedValidationException(
    'Invalid input',
    0,
    null,
    ErrorCodeEnum::INVALID_ARGUMENT,
    422
);

// Forbidden: 400 (Client Error) -> 500 (Server Error)
// This will throw a LogicException immediately.
```

### Error Code Override

You can specify a different error code, but it **must belong to the exception category**.

```php
use Maatify\Exceptions\Exception\Authentication\SessionExpiredMaatifyException;
use Maatify\Exceptions\Enum\ErrorCodeEnum;

// Allowed: Error code matches category (AUTHENTICATION)
throw new SessionExpiredMaatifyException(
    'Please log in again',
    0,
    null,
    ErrorCodeEnum::SESSION_EXPIRED
);

// Forbidden: Mismatched Category (e.g., using DATABASE_CONNECTION_FAILED in a ValidationException)
// This will throw a LogicException.
```

---

## 3️⃣ Wrapping and Escalation

When catching and re-throwing exceptions, simply pass the previous exception as the third argument. The library handles the rest.

```php
try {
    $db->connect();
} catch (\PDOException $e) {
    // Wrap the low-level PDO exception in a System Exception
    throw new DatabaseConnectionMaatifyException('Database failure', 0, $e);
}
```

If you wrap a high-severity exception in a low-severity one, the system automatically escalates it.

**Example:**
*   **Original:** `SystemMaatifyException` (500)
*   **Wrapper:** `BusinessRuleMaatifyException` (422)
*   **Result:** The final exception behaves as a `SystemMaatifyException` (500), ensuring monitoring tools detect the critical failure.

---

## 4️⃣ Using the Interface

If you are building middleware or logging services, type-hint against the interface, not the concrete class.

```php
use Maatify\Exceptions\Contracts\ApiAwareExceptionInterface;

function handleException(ApiAwareExceptionInterface $e)
{
    $status = $e->getHttpStatus();
    $category = $e->getCategory()->value;
    $code = $e->getErrorCode()->value;
    $meta = $e->getMeta();

    // Log structured error...
}
```
