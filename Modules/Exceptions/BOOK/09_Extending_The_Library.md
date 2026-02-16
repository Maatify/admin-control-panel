# Extending The Library

The library is designed to be extensible while maintaining taxonomy integrity.

## Adding a New Exception

1.  **Extend a Family:** Always extend an existing concrete exception class (e.g., `ValidationMaatifyException`).
2.  **Define Default Code:** Override `defaultErrorCode()` to return the correct `ErrorCodeEnum` value.
3.  **Define Constructor:** If necessary, provide a default message.

### Example

```php
use Maatify\Exceptions\Exception\Validation\ValidationMaatifyException;
use Maatify\Exceptions\Enum\ErrorCodeEnum;

class InvalidEmailException extends ValidationMaatifyException
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::INVALID_ARGUMENT;
    }
}
```

## Creating a New Family (Rare)

If you need a new exception family (e.g., `PaymentProcessingError`), you must:

1.  **Add to `ErrorCategoryEnum`:** (Requires library update)
2.  **Add to `ErrorCodeEnum`:** (Requires library update)
3.  **Create Abstract Base Class:** Extend `MaatifyException` and implement `defaultCategory()`.
4.  **Register in `MaatifyException::SEVERITY_RANKING`:** (Requires library update)
5.  **Register in `MaatifyException::ALLOWED_ERROR_CODES`:** (Requires library update)

**Note:** Adding new families requires modifying the core library itself. Consider if your exception fits into an existing category (e.g., `PaymentProcessingError` -> `System` or `BusinessRule`).
