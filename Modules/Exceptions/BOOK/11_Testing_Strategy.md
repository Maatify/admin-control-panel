# Testing Strategy

This section outlines how to test exception handling in your application.

## 1. Test for Exception Class

When writing unit tests (e.g., PHPUnit), assert that the expected `MaatifyException` class is thrown.

```php
public function test_invalid_email_throws_exception()
{
    $this->expectException(InvalidArgumentMaatifyException::class);

    try {
        $service->createUser('invalid-email');
    } catch (InvalidArgumentMaatifyException $e) {
        $this->assertSame(ErrorCodeEnum::INVALID_ARGUMENT, $e->getErrorCode());
        throw $e;
    }
}
```

## 2. Test Escalation Scenarios

If you are wrapping exceptions, verify that the final exception has the expected category and status.

```php
public function test_escalation_system_to_business()
{
    $systemError = new DatabaseConnectionMaatifyException('DB Error');
    // Using anonymous class because BusinessRuleMaatifyException is abstract
    $wrapper = new class('Business Error', 0, $systemError) extends BusinessRuleMaatifyException {};

    // Category escalated from BUSINESS_RULE (40) to SYSTEM (90)
    $this->assertSame(ErrorCategoryEnum::SYSTEM, $wrapper->getCategory());

    // Status escalated from 422 to 503
    $this->assertSame(503, $wrapper->getHttpStatus());
}
```

## 3. Verify Constraints

Ensure that your custom exceptions adhere to the taxonomy.

```php
public function test_custom_exception_constraints()
{
    $this->expectException(LogicException::class);

    // ValidationMaatifyException is abstract
    // Attempting to override with an invalid code via anonymous class
    new class(
        'Error',
        0,
        null,
        ErrorCodeEnum::DATABASE_CONNECTION_FAILED
    ) extends ValidationMaatifyException {};
}
```
