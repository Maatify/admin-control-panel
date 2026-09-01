# Storage Module Tests

Comprehensive test suite for the Maatify Storage Module.

## Running Tests

### Run All Tests
```bash
cd Modules/Storage
../../../vendor/bin/phpunit
```

### Run Specific Test Suite
```bash
# Validators
../../../vendor/bin/phpunit tests/Unit/Validators/

# Services
../../../vendor/bin/phpunit tests/Unit/Services/

# Exceptions
../../../vendor/bin/phpunit tests/Unit/Exception/

# Configuration
../../../vendor/bin/phpunit tests/Unit/Config/
```

### Run With Coverage Report
```bash
../../../vendor/bin/phpunit --coverage-html=.phpunit.cache/code-coverage
```

## Test Structure

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── ImageUploadServiceTest.php    (Semantic naming, validation)
│   │   └── VideoUploadServiceTest.php    (Semantic naming, large files)
│   ├── Validators/
│   │   ├── ExtensionValidatorTest.php    (File type validation)
│   │   ├── SizeValidatorTest.php         (File size limits)
│   │   └── ImageDimensionsValidatorTest.php (Image dimension constraints)
│   ├── Exception/
│   │   └── ExceptionTest.php             (All exception types)
│   ├── Config/
│   │   └── StorageConfigTest.php         (Configuration loading)
│   └── StorageModuleTestCase.php         (Base test class with utilities)
└── bootstrap.php                          (Test environment setup)
```

## Key Features Tested

### ImageUploadService ✅
- ✅ Default extensions (jpg, jpeg, png, webp)
- ✅ Custom size limits
- ✅ Image dimension constraints
- **✅ Semantic basename generation** (business context in filenames)
  - Product slugs: `product-premium-tier-abc123def456.jpg`
  - Payment methods: `visa-mastercard-001-abc123def456.jpg`
  - Composite keys: `payment-method-123-lang-1-abc123def456.jpg`
- ✅ Unique filename generation (even with same basename)
- ✅ Case normalization and special character handling

### VideoUploadService ✅
- ✅ Default extensions (mp4, webm, mov, avi)
- ✅ Large file handling (500MB+)
- **✅ Semantic basename generation**
  - Course content: `course-advanced-php-lesson-5-abc123def456.mp4`
  - Webinars: `webinar-2024-march-keynote-abc123def456.mp4`
  - Testimonials: `customer-testimonial-001-abc123def456.mp4`

### Validators ✅
- ✅ **ExtensionValidator**: File type validation with case-insensitivity
- ✅ **SizeValidator**: File size enforcement (0-500MB+)
- ✅ **ImageDimensionsValidator**: Image dimension constraints (min/max width/height)

### Exception Handling ✅
- ✅ ConfigurationException (environment variables, unsupported drivers)
- ✅ FileUploadException (PHP upload errors)
- ✅ InvalidFileException (validation failures)
- ✅ StorageException (base exception)

### Configuration ✅
- ✅ Local storage config
- ✅ DigitalOcean Spaces config
- ✅ Environment variable loading
- ✅ Default values
- ✅ Readonly properties

## Semantic Naming System

The Storage Module supports **semantic basenames** to preserve business context in filenames:

### Without Semantic Naming (Generic)
```
images/
├── image-abc123def456.jpg
├── image-def456ghi789.jpg
└── image-jkl012mno345.jpg
```

### With Semantic Naming (Traceable)
```
products/
├── product-premium-tier-abc123def456.jpg
├── product-basic-tier-def456ghi789.jpg
└── product-enterprise-tier-jkl012mno345.jpg

payment-methods/
├── visa-mastercard-001-abc123def456.jpg
└── paypal-express-002-def456ghi789.jpg

payment-methods/translations/
├── payment-method-1-lang-1-abc123def456.jpg
├── payment-method-1-lang-2-def456ghi789.jpg
└── payment-method-2-lang-1-jkl012mno345.jpg
```

**Benefits:**
- ✅ Traceability: Know what entity each file belongs to
- ✅ Organization: Semantic grouping by business context
- ✅ Audit trails: Meaningful context for compliance
- ✅ Debugging: Easy to identify related files
- ✅ Scalability: Maintains meaning as storage grows

## Test Database

Tests use temporary directories in `sys_get_temp_dir()` and are automatically cleaned up after each test.

## Fixtures

The `Fixtures/` directory can contain:
- Sample images for visual validation
- Mock data files
- Test configuration files

(Currently empty, add as needed for advanced tests)

## Coverage Goals

Target coverage:
- **Services**: 95%+ (critical for reliability)
- **Validators**: 100% (all validation paths)
- **Exceptions**: 100% (all error scenarios)
- **Configuration**: 90%+ (environment variations)

View coverage report:
```bash
../../../vendor/bin/phpunit --coverage-html=.phpunit.cache/code-coverage
# Then open: .phpunit.cache/code-coverage/index.html
```

## CI/CD Integration

When this module becomes standalone, add to your CI/CD:

```yaml
# GitHub Actions example
- name: Run Storage Module Tests
  run: |
    cd Modules/Storage
    vendor/bin/phpunit --coverage-html=coverage
    
- name: Upload Coverage
  uses: codecov/codecov-action@v3
  with:
    files: ./coverage/index.html
```

## Development Notes

- Tests are **independent** and can run in any order
- **No external dependencies** required (except vendor libraries)
- Temp files are **automatically cleaned up**
- Tests use **mocks** to avoid filesystem during unit tests
- Integration tests can be added later in `tests/Integration/`
