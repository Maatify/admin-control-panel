# Testing Guide — Settings Module

Comprehensive testing guide for the Settings module.

---

## Test Coverage

### Unit Tests (10 test classes, 70+ test methods)

#### 1. **Exception Classes** (2 test classes)
- `SettingsNotFoundExceptionTest` — exception factory methods
- `SettingsInvalidArgumentExceptionTest` — validation error messages

#### 2. **DTO Classes** (3 test classes)
- `SettingDTOTest` — full record serialization
- `SettingListItemDTOTest` — list item representation
- `SettingCollectionDTOTest` — iterable collection behavior

#### 3. **Commands** (1 test class)
- `UpdateSettingValueCommandTest` — constructor validation (empty fields, whitespace)

#### 4. **Repositories** (2 test classes)
- `PdoAdminSettingQueryRepositoryTest` — query operations (find, list, pagination, filters)
- `PdoAdminSettingCommandRepositoryTest` — update operations, edge cases

#### 5. **Services** (2 test classes)
- `AdminSettingServiceTest` — business logic, editability checks, orchestration
- `SettingValueServiceTest` — value retrieval, type casting, default fallbacks

---

## Running Tests

### Using PHPUnit

```bash
cd Modules/Settings

# Run all tests
vendor/bin/phpunit

# Run specific test class
vendor/bin/phpunit tests/Admin/Setting/Service/AdminSettingServiceTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage

# Run with verbose output
vendor/bin/phpunit -v
```

### Using Composer Script

Add to `composer.json`:

```json
{
  "scripts": {
    "test": "phpunit",
    "test:coverage": "phpunit --coverage-html coverage",
    "test:watch": "phpunit-watch"
  }
}
```

Then:

```bash
composer test
```

---

## Test Structure

Each test class follows this pattern:

```php
final class SomeServiceTest extends TestCase
{
    private SomeDependency $dependency;
    private SomeService $service;

    protected function setUp(): void
    {
        // Create mocks and initialize SUT
        $this->dependency = $this->createMock(SomeDependencyInterface::class);
        $this->service = new SomeService($this->dependency);
    }

    public function testSuccessfulScenario(): void
    {
        // Arrange
        $this->dependency->method('someMethod')->willReturn($expectedValue);

        // Act
        $result = $this->service->doSomething();

        // Assert
        self::assertSame($expectedValue, $result);
    }

    public function testExceptionCase(): void
    {
        // Arrange
        $this->dependency->method('someMethod')->willReturn(null);
        $this->expectException(SomeNotFoundException::class);

        // Act & Assert
        $this->service->doSomething();
    }
}
```

---

## Test Categories

### 1. Unit Tests (Mocked Dependencies)

- **Exception tests** — named constructor methods produce correct messages
- **DTO tests** — serialization, immutability, iteration
- **Command tests** — constructor validation catches invalid input
- **Service tests** — business logic with mocked repositories

**Example:**

```php
public function testUpdateValueNotEditable(): void
{
    $dto = new SettingDTO(..., isAdminEditable: false, ...);
    $this->queryRepo->method('findByKey')->willReturn($dto);

    $this->expectException(SettingsInvalidArgumentException::class);
    $command = new UpdateSettingValueCommand('system_id', '456');
    $this->service->updateValue($command);
}
```

### 2. Integration Tests (Real Database)

Repository tests use an in-memory SQLite database:

```php
protected function setUp(): void
{
    $this->pdo = new PDO('sqlite::memory:');
    $this->createSchema();
    $this->seedData();
}
```

**Coverage:**

- Query correctness (find, list, pagination, filters)
- Insert/update operations
- Edge cases (special characters, long strings, null values)
- Transaction behavior

---

## Test Scenarios

### Exception Tests

✅ `SettingsNotFoundException::withKey()` — message format correct  
✅ Empty and special character handling  

### DTO Tests

✅ Construction with all fields  
✅ JSON serialization includes all fields  
✅ JSON serialization excludes null notes  
✅ Collection iteration and JSON serialization  

### Command Tests

✅ Valid construction  
✅ Empty `settingKey` throws  
✅ Empty `settingValue` throws  
✅ Whitespace-only fields throw  

### Repository Tests

**Query Repository:**
- ✅ Find by key — returns DTO
- ✅ Find by key — returns null when not found
- ✅ List all settings with pagination
- ✅ List with global search
- ✅ List with column filters (is_admin_editable, value_type)
- ✅ Pagination: page 1, page 2, custom per_page
- ✅ List as key=>value map

**Command Repository:**
- ✅ Update value — returns true on success
- ✅ Update value — returns false when not found
- ✅ Update with long strings (255 chars)
- ✅ Update with special characters (quotes, backslashes)
- ✅ Multiple sequential updates
- ✅ Database reflects changes

### Service Tests

**AdminSettingService:**
- ✅ `getByKey()` — returns DTO
- ✅ `getByKey()` — throws NotFoundException
- ✅ `updateValue()` — calls repository when editable
- ✅ `updateValue()` — throws when not editable
- ✅ `updateValue()` — throws when not found
- ✅ `list()` — delegates to repository
- ✅ `listAsKeyValue()` — returns all settings

**SettingValueService:**
- ✅ `getValue()` — returns raw value
- ✅ `getBool()` — "0" → false, "1" → true
- ✅ `getInt()` — casts to integer
- ✅ `getString()` — returns string
- ✅ `getOrDefault()` — returns value if found, default if not
- ✅ `getOrDefaultBool()` — no exception, returns default
- ✅ `getOrDefaultInt()` — no exception, returns default

---

## Coverage Goals

| Layer | Target | Method |
|-------|--------|--------|
| Exceptions | 100% | Named constructors tested |
| DTOs | 100% | Construction, JSON, iteration |
| Commands | 100% | Valid + all validation paths |
| Repositories | 90%+ | Query + data mutation + pagination |
| Services | 90%+ | Happy path + error cases |

---

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [8.2, 8.3, 8.4]
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - run: composer install
      - run: vendor/bin/phpunit
      - run: vendor/bin/phpstan analyze --level max src
```

---

## Common Issues

### "Test database is locked"

SQLite in-memory databases are per-connection. Each test should have its own:

```php
protected function setUp(): void
{
    $this->pdo = new PDO('sqlite::memory:'); // New DB per test ✓
}
```

### "Class not found"

Ensure `tests/` is in the autoload-dev of `composer.json`:

```json
{
  "autoload-dev": {
    "psr-4": {
      "Maatify\\Settings\\Tests\\": "tests/"
    }
  }
}
```

### "Mock method not called"

Verify the mock was set up and the method was actually called:

```php
$this->queryRepo->expects(self::once())->method('findByKey');
$this->service->getByKey('maintenance');
```

---

## Best Practices

1. **One assertion per test when possible** — easier to debug
2. **Mocks for dependencies** — unit tests are fast
3. **Real database for repositories** — validate SQL correctness
4. **Test edge cases** — empty strings, null, special chars, boundary values
5. **Descriptive test names** — `testUpdateValueNotEditableThrows()` is better than `testUpdate()`
6. **Arrange-Act-Assert pattern** — clear test structure

---

## Performance

- All unit tests: < 1 second
- All integration tests: < 2 seconds
- Full suite: < 3 seconds (no external I/O)

---

## See Also

- README.md — quick start
- SETTINGS_MODULE_REFERENCE.md — API documentation
- MODULE_BUILDING_STANDARD.md — module architecture
