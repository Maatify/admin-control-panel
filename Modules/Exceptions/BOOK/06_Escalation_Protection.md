# Escalation Protection

`maatify/exceptions` features a deterministic escalation mechanism.

## The Problem: Swallowed Errors

A common pattern in PHP is to wrap exceptions:

```php
try {
    $db->connect(); // Throws 503 System Error
} catch (Exception $e) {
    // Attempting to wrap in a business rule exception
    throw new class("Cannot process request", 0, $e) extends BusinessRuleMaatifyException {};
}
```

This effectively **hides the system failure**. Monitoring tools see a 422 (Business Rule) instead of a critical 503 (System Failure).

## The Solution: Automatic Escalation

When wrapping an exception using `MaatifyException`:

1.  **Category Severity Check:** The library compares the severity of the new exception against the previous one.
2.  **HTTP Status Check:** The library compares the HTTP status codes.
3.  **Result:** The final exception adopts the **higher severity** category and status.

### Severity Ranking (High to Low)

1.  `SYSTEM` (90)
2.  `RATE_LIMIT` (80)
3.  `AUTHENTICATION` (70)
4.  `AUTHORIZATION` (60)
5.  `VALIDATION` (50)
6.  `BUSINESS_RULE` (40)
7.  `CONFLICT` (30)
8.  `NOT_FOUND` (20)
9.  `UNSUPPORTED` (10)

### Example

*   **Original:** `SystemMaatifyException` (Severity 90, Status 503)
*   **Wrapper:** `BusinessRuleMaatifyException` (Severity 40, Status 422)

**Final Outcome:**
*   **Category:** `SYSTEM` (Escalated from BusinessRule)
*   **Status:** 503 (Escalated from 422)

This guarantees that critical errors are **never masked**.
