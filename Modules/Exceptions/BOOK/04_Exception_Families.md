# Exception Families

Exceptions are categorized into 9 main families, each backed by an abstract base class.

## 1. SystemMaatifyException

*   **Category:** `SYSTEM`
*   **Default Status:** 500
*   **Usage:** For critical failures like database errors, filesystem failures, or external API outages.
*   **Concrete:** `DatabaseConnectionMaatifyException`

## 2. RateLimitMaatifyException

*   **Category:** `RATE_LIMIT`
*   **Default Status:** 429
*   **Default `isRetryable()`:** `true`
*   **Usage:** When a client exceeds the allowed request rate.
*   **Concrete:** `TooManyRequestsMaatifyException`

## 3. AuthenticationMaatifyException

*   **Category:** `AUTHENTICATION`
*   **Default Status:** 401
*   **Usage:** When a user is not logged in or has an invalid session.
*   **Concrete:** `UnauthorizedMaatifyException`, `SessionExpiredMaatifyException`

## 4. AuthorizationMaatifyException

*   **Category:** `AUTHORIZATION`
*   **Default Status:** 403
*   **Usage:** When a user is logged in but lacks permission.
*   **Concrete:** `ForbiddenMaatifyException`

## 5. ValidationMaatifyException

*   **Category:** `VALIDATION`
*   **Default Status:** 400
*   **Usage:** For invalid client input (e.g., malformed email, missing field).
*   **Concrete:** `InvalidArgumentMaatifyException`

## 6. BusinessRuleMaatifyException

*   **Category:** `BUSINESS_RULE`
*   **Default Status:** 422
*   **Usage:** For domain-specific logic (e.g., "Cannot cancel shipped order").
*   **Concrete:** `BusinessRuleMaatifyException` (Abstract)

## 7. ConflictMaatifyException

*   **Category:** `CONFLICT`
*   **Default Status:** 409
*   **Usage:** For duplicate resource creation or data inconsistency.
*   **Concrete:** `GenericConflictMaatifyException`

## 8. NotFoundMaatifyException

*   **Category:** `NOT_FOUND`
*   **Default Status:** 404
*   **Usage:** When a requested resource does not exist.
*   **Concrete:** `ResourceNotFoundMaatifyException`

## 9. UnsupportedMaatifyException

*   **Category:** `UNSUPPORTED`
*   **Default Status:** 409
*   **Usage:** When an operation is valid but not supported in the current context.
*   **Concrete:** `UnsupportedOperationMaatifyException`
