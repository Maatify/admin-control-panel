# Taxonomy

The core of `maatify/exceptions` is its strict taxonomy system.

## ErrorCategoryEnum

The `ErrorCategoryEnum` defines the 9 core families of exceptions.

| Category | Description |
| -------- | ----------- |
| `SYSTEM` | Critical system failures (database, network, file). |
| `RATE_LIMIT` | Too many requests (client throttling). |
| `AUTHENTICATION` | Authentication failures (login required). |
| `AUTHORIZATION` | Forbidden access (permission required). |
| `VALIDATION` | Invalid input data (bad request). |
| `BUSINESS_RULE` | Domain-specific logic violation. |
| `CONFLICT` | Resource conflict (duplicate entry). |
| `NOT_FOUND` | Resource not found (404). |
| `UNSUPPORTED` | Unsupported operation or method. |

## ErrorCodeEnum

The `ErrorCodeEnum` provides distinct error codes for specific scenarios.

| Code | Usage |
| ---- | ----- |
| `MAATIFY_ERROR` | Generic system error. |
| `INVALID_ARGUMENT` | Generic validation error. |
| `BUSINESS_RULE_VIOLATION` | Generic business logic error. |
| `RESOURCE_NOT_FOUND` | Resource not found. |
| `CONFLICT` | Resource conflict. |
| `UNAUTHORIZED` | Authentication required. |
| `SESSION_EXPIRED` | Session expired. |
| `FORBIDDEN` | Access denied. |
| `UNSUPPORTED_OPERATION` | Operation not supported. |
| `DATABASE_CONNECTION_FAILED` | Database connection error. |
| `TOO_MANY_REQUESTS` | Rate limit exceeded. |

## Strict Mapping

The library strictly enforces which Error Codes can be used with which Categories. For example, you cannot use `DATABASE_CONNECTION_FAILED` with a `VALIDATION` category. Attempting to do so will result in a `LogicException`.
