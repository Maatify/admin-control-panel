# Best Practices

Follow these guidelines for effective exception handling.

## 1. Throw Concrete Classes

Prefer throwing a specific exception (e.g., `InvalidArgumentMaatifyException`) over a generic one (e.g., `ValidationMaatifyException`).

```php
// Good
throw new InvalidArgumentMaatifyException('Email is invalid');

// Bad
throw new ValidationMaatifyException('Email is invalid', 0, null, ErrorCodeEnum::INVALID_ARGUMENT);
```

## 2. Wrap Low-Level Exceptions

When dealing with third-party libraries or PHP built-ins, wrap them in a semantic `MaatifyException` immediately.

```php
try {
    file_get_contents($path);
} catch (ErrorException $e) {
    throw new SystemMaatifyException('Failed to read config file', 0, $e);
}
```

## 3. Don't Catch What You Can't Handle

Only catch exceptions you intend to recover from or re-throw. Let others bubble up to the global handler.

## 4. Use Constants for Messages (Optional)

Consider using class constants for exception messages to ensure consistency across the application.

```php
class UserNotFoundException extends NotFoundMaatifyException
{
    public const MESSAGE = 'User not found.';

    public function __construct() {
        parent::__construct(self::MESSAGE);
    }
}
```

## 5. Avoid Logic in Exceptions

Exceptions should carry data, not logic. Do not put complex business rules inside the exception constructor.
