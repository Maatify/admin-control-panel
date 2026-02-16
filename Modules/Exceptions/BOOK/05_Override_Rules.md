# Override Rules

The `MaatifyException` base class allows developers to override default metadata (HTTP Status, Error Code) but enforces strict guardrails.

## 1. Category Immutability

You **cannot** override the category of an exception.

*   `ValidationMaatifyException` will always be `VALIDATION`.
*   `SystemMaatifyException` will always be `SYSTEM`.

This prevents "Taxonomy Drift" where exceptions lose their semantic meaning.

## 2. Error Code Constraints

If you provide an `$errorCodeOverride` in the constructor:

1.  The code **must exist** in the `ALLOWED_ERROR_CODES` mapping for that category.
2.  If it does not match, a `LogicException` is thrown immediately.

**Example:**
*   `ValidationMaatifyException` allows `INVALID_ARGUMENT`.
*   It forbids `DATABASE_CONNECTION_FAILED`.

*(Note: Since `ValidationMaatifyException` is abstract, these rules apply to any concrete class extending it.)*

## 3. HTTP Status Class Guard

If you provide an `$httpStatusOverride`:

1.  It **must match the default status class** (Client Error vs Server Error).
2.  `4xx` defaults can be overridden with other `4xx` codes.
3.  `5xx` defaults can be overridden with other `5xx` codes.
4.  **Cross-class overrides are forbidden.** (e.g., 400 -> 500).

This ensures that a client-side error (Validation) never accidentally reports a server-side failure (System) to monitoring tools.
