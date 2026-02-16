# Security Model

## isSafe() and Message Exposure

`ApiAwareExceptionInterface` defines an `isSafe()` method.

### Safe Exceptions (isSafe() === true)

These exceptions are safe to expose to end-users (e.g., in an API response).

*   `ValidationMaatifyException`
*   `BusinessRuleMaatifyException`
*   `AuthenticationMaatifyException`
*   `AuthorizationMaatifyException`
*   `RateLimitMaatifyException`
*   `ConflictMaatifyException`
*   `NotFoundMaatifyException`
*   `UnsupportedMaatifyException`

**Warning:** Do **not** include sensitive data (tokens, internal IDs, passwords) in the exception message, even for safe exceptions.

### Unsafe Exceptions (isSafe() === false)

These exceptions **must never** be exposed to end-users. They contain internal details (stack traces, SQL errors, file paths).

*   `SystemMaatifyException`
*   `DatabaseConnectionMaatifyException`
*   `MaatifyException` (Base class default)

## Responsibility

It is the responsibility of the **exception handler** (middleware, framework) to check `isSafe()` before rendering a response. If `isSafe()` is false, the handler should log the error and return a generic "Internal Server Error" message.
