# Testing Strategy

This section outlines how to test exception handling in your application.

## 1. Test for Exception Class

When writing unit tests (e.g., PHPUnit), assert that the expected `MaatifyException` class is thrown.

```php
public function test_invalid_email_throws_exception()
{
    $this->expectException(InvalidArgumentMaatifyException::class);
    $this->expectExceptionCode(ErrorCodeEnum::INVALID_ARGUMENT->value);

    $service->createUser('invalid-email');
}
```

## 2. Test Escalation Scenarios

If you are wrapping exceptions, verify that the final exception has the expected category and status.

```php
public function test_escalation_system_to_business()
{
    $systemError = new DatabaseConnectionMaatifyException('DB Error');
    $wrapper = new BusinessRuleMaatifyException('Business Error', 0, $systemError);

    $this->assertSame(ErrorCategoryEnum::SYSTEM, $wrapper->getCategory());
    $this->assertSame(503, $wrapper->getHttpStatus());
}
```

## 3. Verify Constraints

Ensure that your custom exceptions adhere to the taxonomy.

```php
public function test_custom_exception_constraints()
{
    $this->expectException(LogicException::class);

    // Attempting to override with an invalid code
    new ValidationMaatifyException(
        'Error',
        0,
        null,
        ErrorCodeEnum::DATABASE_CONNECTION_FAILED
    );
}
```
