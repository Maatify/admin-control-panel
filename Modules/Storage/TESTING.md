# Storage Module Testing Guide

## Running Tests

### Quick Start
```bash
cd /path/to/project
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php Modules/Storage/tests/Unit --no-coverage
```

### With Coverage Report
```bash
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php Modules/Storage/tests/Unit --coverage-html=Modules/Storage/.phpunit.cache/code-coverage
# View: Modules/Storage/.phpunit.cache/code-coverage/index.html
```

### Run Specific Test Suite
```bash
# Validators
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php Modules/Storage/tests/Unit/Validators

# Services
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php Modules/Storage/tests/Unit/Services

# Exceptions
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php Modules/Storage/tests/Unit/Exception

# Configuration
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php Modules/Storage/tests/Unit/Config

# Storage Adapters (LocalStorageAdapter + DOSpacesStorageAdapter)
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php Modules/Storage/tests/Unit/Adapters
```

## Test Coverage

### Current Status: ✅ **All Storage tests passing**

**Test Breakdown:**

| Suite | Tests | Status |
|-------|-------|--------|
| Upload Services | 45 | ✅ PASS |
| Reader Adapters | 18 | ✅ PASS |
| Validators | 37 | ✅ PASS |
| Storage Adapters | 22 | ✅ PASS |
| Exceptions | 13 | ✅ PASS |
| Configuration | 9 | ✅ PASS |
| Factories | 10 | ✅ PASS |
| **Total** | **154** | **✅ PASS** |

## Test Suites

### 1. Storage Adapter Tests (22 tests)

#### LocalStorageAdapterTest (10 tests)
- ✅ `storeFromPath_copiesFileToDestination` - Copies source file to configured storage root
- ✅ `storeFromPath_preservesSourceFile` - Does **not** delete the source (safe for retry)
- ✅ `storeFromPath_returnsStoredFileWithCorrectPath` - Returns `StoredFile` with relative path and URL
- ✅ `storeFromPath_stripsLeadingSlashFromDestinationPath` - Normalises `/images/photo.jpg` → `images/photo.jpg`
- ✅ `storeFromPath_createsDestinationDirectoryIfMissing` - `mkdir -p` for nested paths
- ✅ `storeFromPath_throwsAdapterExceptionWhenSourceMissing` - Throws `AdapterException` (not PHP warning)
- ✅ `presign_returnsLocalUrl` - Delegates to `url()` — local storage has no signing concept
- ✅ `presign_ignoresExpiryParameter` - Short and long TTLs return identical URLs
- ✅ `url_prependsBaseUrlToRelativePath` - `/files` + `products/image.jpg` → `/files/products/image.jpg`
- ✅ `url_stripsLeadingSlashFromRelativePath` - Leading slash on path is normalised

#### DOSpacesStorageAdapterTest (12 tests)

Uses `\Aws\MockHandler` + `\Aws\Result` (AWS SDK's own mock — not Guzzle).

**url()**
- ✅ `url_returnsEmptyStringWhenCdnUrlIsNull` - Private adapter has no public URL
- ✅ `url_returnsEmptyStringWhenCdnUrlIsEmpty` - Empty string treated same as null
- ✅ `url_prependsCdnUrlForPublicAdapter` - `https://cdn.example.com` + path → full URL
- ✅ `url_stripsLeadingSlashFromRelativePath` - Leading slash on path is normalised

**presign()**
- ✅ `presign_returnsPresignedUrlForPrivateAdapter` - Contains `X-Amz-` params (pure local crypto, no HTTP call)
- ✅ `presign_forPublicAdapterReturnsCdnUrlWithoutSigning` - CDN URL returned as-is; no `X-Amz-` params
- ✅ `presign_expiryIsReflectedInSignedUrl` - `X-Amz-Expires=86400` in signed URL

**storeFromPath()**
- ✅ `storeFromPath_uploadsFileAndReturnsStoredFile` - Calls `putObject`, returns `StoredFile` with correct path
- ✅ `storeFromPath_preservesSourceFile` - Source file still exists after upload
- ✅ `storeFromPath_stripsLeadingSlashFromDestinationKey` - Key normalised in returned path
- ✅ `storeFromPath_throwsAdapterExceptionWhenSourceMissing` - Throws before attempting upload
- ✅ `storeFromPath_closesStreamEvenWhenUploadFails` - `finally` block closes file handle on AWS exception

### 3. Validators Tests (37 tests)

#### MimeTypeValidatorTest (23 tests) - **NEW: Security Validation**
- ✅ `testValidatesJpegByMagicBytes` - JPEG magic bytes (FF D8 FF)
- ✅ `testValidatesPngByMagicBytes` - PNG magic bytes (89 50 4E 47)
- ✅ `testValidatesGifByMagicBytes` - GIF magic bytes (47 49 46)
- ✅ `testValidatesWebmByMagicBytes` - WebM magic bytes (1A 45 DF A3)
- ✅ `testValidatesBmpByMagicBytes` - BMP magic bytes (42 4D)
- ✅ `testValidatesWebpByMagicBytes` - WebP magic bytes (RIFF + WEBP)
- ✅ `testValidatesIcoByMagicBytes` - ICO magic bytes (00 00 01 00)
- ✅ **`testRejectsExecutableDisguisedAsJpeg`** - **CRITICAL SECURITY TEST**: Blocks `virus.jpg` (ELF executable)
- ✅ **`testRejectsWindowsExecutableDisguisedAsPng`** - Blocks `trojan.png` (PE executable)
- ✅ **`testRejectsShellScriptDisguisedAsImage`** - Blocks `script.jpg` (shell script)
- ✅ `testRejectsWrongMimeType` - Rejects JPEG sent as PNG
- ✅ `testRejectsUnknownMimeType` - Rejects random data
- ✅ `testHandlesShortFiles` - Handles < 4 byte files gracefully
- ✅ `testValidatesMp4ByMagicBytes` - MP4 magic bytes (ftyp)
- ✅ `testValidatesAviByMagicBytes` - AVI magic bytes (RIFF + AVI)
- ✅ `testImageTypesHelper` - Static helper for image MIME types
- ✅ `testVideoTypesHelper` - Static helper for video MIME types
- ✅ `testValidatesMp3ByMagicBytes` - MP3 MPEG frame header (FF FB)
- ✅ **`testValidatesMp3WithId3Tag`** - **CRITICAL**: MP3 with ID3v2 tag (how REAL MP3s start!)
- ✅ `testValidatesWavByMagicBytes` - WAV magic bytes (RIFF + WAVE)
- ✅ `testValidatesOggByMagicBytes` - OGG magic bytes (OggS)
- ✅ `testRejectsExecutableDisguisedAsAudio` - **SECURITY**: Blocks `song.mp3` (PE executable)
- ✅ `testAudioTypesHelper` - Static helper for audio MIME types

**Why These Tests Matter:**
- Validates that our MIME type detection cannot be bypassed
- Critical security tests prevent file spoofing attacks
- Proves content-based validation works (not just extension)

#### ExtensionValidatorTest (5 tests)
- ✅ `testValidatesAllowedExtensions` - Validates correct file extensions
- ✅ `testRejectsDisallowedExtensions` - Rejects invalid extensions
- ✅ `testHandlesFilesWithoutExtension` - Handles edge case (no extension)
- ✅ `testCaseInsensitiveValidation` - Case-insensitive extension checking
- ✅ `testMultipleExtensionHandling` - Validates multiple variations (jpeg, jpg, jpe)

#### SizeValidatorTest (5 tests)
- ✅ `testValidatesFilesWithinLimit` - Accepts files under limit
- ✅ `testRejectsFilesExceedingLimit` - Rejects oversized files
- ✅ `testAcceptsExactlyLimitSize` - Boundary test (exact size)
- ✅ `testHandlesNullFileSize` - Handles null file size
- ✅ `testLargeFileLimits` - Validates large file limits (500MB+)

#### ImageDimensionsValidatorTest (4 tests)
- ✅ `testValidatesImageWithinDimensions` - Validates correct dimensions
- ✅ `testRejectsImageTooSmall` - Enforces minimum dimensions
- ✅ `testRejectsImageTooLarge` - Enforces maximum dimensions
- ✅ `testAcceptsExactBoundaries` - Boundary testing (min/max)
- Uses GD library to create actual JPEG images for testing

## 4. Reader Adapter Tests (18 tests)

### LocalStorageReaderTest (18 tests) - **File Reading & Access Control**

#### Access URL Generation
- ✅ `testGetAccessUrlReturnsPathForExistingFile` - Returns relative path for local files
- ✅ `testGetAccessUrlDoesNotVerifyExistence` - Does NOT throw if file doesn't exist (lazy validation)
- ✅ `testGetAccessUrlWithCustomExpiration` - Respects expiration parameter

#### File Existence Checking
- ✅ `testExistsReturnsTrueForExistingFile` - Returns true for files
- ✅ `testExistsReturnsFalseForNonExistentFile` - Returns false for missing files
- ✅ `testExistsReturnsFalseForDirectory` - **CRITICAL**: Rejects directories (is_file check)

#### Metadata Retrieval
- ✅ `testGetMetadataReturnsFileMetadata` - Retrieves size, type, modification time
- ✅ `testGetMetadataThrowsFileNotFoundExceptionForNonExistentFile` - Throws on missing file
- ✅ `testGetMetadataThrowsFileNotFoundExceptionForDirectory` - **CRITICAL**: Rejects directories
- ✅ `testGetMetadataDetectsMimeType` - Detects MIME type (magic bytes + fallback)

#### Path Validation (Security)
- ✅ `testPathWithLeadingSlashIsRejected` - Prevents absolute paths
- ✅ `testValidatePathThrowsExceptionForInvalidPath` - Data provider tests 6 attack types:
  - `'empty path'` - Empty string rejected
  - `'traversal attempt ..'` - Directory traversal blocked
  - `'traversal attempt /../'` - Dotdot slash blocked
  - `'null byte'` - Null bytes rejected
  - `'double slash'` - Path normalization enforced
  - `'backslash'` - Windows paths rejected
- ✅ `testDoubleEncodedTraversalIsRejected` - **CRITICAL SECURITY**: %252e%252e → %2e%2e → .. blocked

**Why These Tests Matter:**
- Validates symmetric Reader implementation matches Upload security level
- Proves directory handling is correct (files only, not directories)
- Critical security tests prevent path traversal attacks
- Ensures lazy validation (getAccessUrl() doesn't check existence, by design)

### 5. Services Tests (45 tests)

#### ImageUploadServiceTest (12 tests)
- ✅ `testUploadsImageWithDefaults` - Basic upload with all defaults
- ✅ `testUploadsImageWithCustomBaseName` - **Semantic naming**: `premium-tier-product-abc123def456.jpg`
- ✅ `testSemanticNamingPreservesBusinessContext` - Tests semantic context:
  - Product slugs: `awesome-product-v2-abc123def456.jpg`
  - Payment methods: `visa-mastercard-001-abc123def456.jpg`
  - Composite identifiers: `payment-method-123-lang-1-abc123def456.jpg`
- ✅ `testSemanticNamingSanitizesInput` - Removes special characters from baseName
- ✅ `testUploadsImageWithCustomExtensions` - Custom extension validation
- ✅ `testUploadsImageWithSizeConstraint` - Custom size limits
- ✅ `testRejectsImageExceedingSizeLimit` - Size validation enforced
- ✅ `testRejectsDisallowedExtension` - Extension validation enforced
- ✅ `testSanitizesFilenamesToLowercase` - Case normalization
- ✅ `testGeneratesUniqueFilenames` - Random suffix ensures uniqueness
- ✅ `testPreservesOriginalExtension` - Keeps original file extension
- ✅ `testNullCustomBaseNameUsesAutoGeneration` - Fallback filename generation

#### VideoUploadServiceTest (14 tests)
- ✅ `testUploadsVideoWithDefaults` - Basic video upload
- ✅ `testUploadsVideoWithCustomBaseName` - **Semantic naming**: `customer-testimonial-001-abc123def456.mp4`
- ✅ `testSemanticNamingForWebinars` - `webinar-2024-march-keynote-abc123def456.mp4`
- ✅ `testSemanticNamingForCourses` - `course-advanced-php-lesson-5-abc123def456.mp4`
- ✅ `testUploadsMp4Video` - Single format support
- ✅ `testUploadsMultipleFormats` - Multiple format support (mp4, webm, mov, avi)
- ✅ `testUploadsVideoWithCustomSizeLimit` - Large file handling (100MB+)
- ✅ `testRejectsVideoExceedingSizeLimit` - Enforces size limits
- ✅ `testRejectsDisallowedFormat` - Format validation
- ✅ `testSanitizesBasenameCaseSensitivity` - Case normalization
- ✅ `testGeneratesUniqueFilenamesEvenWithSameBaseName` - Uniqueness guaranteed
- ✅ `testHandlesSpecialCharactersInBaseName` - Sanitization
- ✅ `testLargeFilesWithSemanticNaming` - Combined constraints

#### AudioUploadServiceTest (19 tests) - **NEW: Audio Upload Support**
- ✅ `testUploadsAudioWithDefaults` - Basic audio upload
- ✅ `testUploadsAudioWithCustomBaseName` - **Semantic naming**: `dua-001-abc123def456.mp3`
- ✅ `testSemanticNamingForPodcasts` - `episode-15-understanding-quran-abc123def456.mp3`
- ✅ `testSemanticNamingForMusic` - `nasheed-beautiful-morning-abc123def456.mp3`
- ✅ `testUploadsMultipleFormats` - Multiple format support (mp3, wav, ogg)
- ✅ `testUploadsAudioWithCustomSizeLimit` - Large file handling (100MB+)
- ✅ `testRejectsAudioExceedingSizeLimit` - Enforces size limits (fixed: now tests actual size)
- ✅ `testRejectsDisallowedFormat` - Format validation
- ✅ `testSanitizesBasenameCaseSensitivity` - Case normalization
- ✅ `testUploadsAllAudioFormatsWithSemanticNaming` - All formats with naming
- ✅ **`testAllowedExtensionsRestrictsMimeTypes`** - **CRITICAL**: Ensures semantic correctness
  - allowedExtensions: ['mp3'] rejects WAV content even if filename is .mp3
  - Prevents mismatch between extension and actual MIME type
- ✅ **`testRejectsUnsupportedCustomExtensionCleanly`** - **CRITICAL**: Extension normalization validation
  - Rejects unsupported extensions (e.g., 'aac') with proper exception
  - Not PHP warning/error but InvalidFileException
- ✅ **`testNormalizesCustomAllowedExtensions`** - Extension normalization
  - Accepts `.MP3` (uppercase with dot) and normalizes to `mp3`
- ✅ **`testAcceptsNormalizedVariantsOfExtensions`** - Extension variant handling
  - All variants ('mp3', 'MP3', '.mp3', '.MP3') are normalized and accepted

### 6. Exception Tests (13 tests)

#### ExceptionTest (13 tests)
- ✅ `testConfigurationExceptionMissingEnvVariable` - Missing env vars
- ✅ `testConfigurationExceptionUnsupportedDriver` - Invalid drivers
- ✅ `testConfigurationExceptionMissingAdapterConfig` - Missing adapter config
- ✅ `testFileUploadExceptionFromErrorCode` - PHP upload error codes
- ✅ `testFileUploadExceptionUnreadableStream` - Stream read failures
- ✅ `testInvalidFileExceptionFileTooLarge` - Size violations
- ✅ `testInvalidFileExceptionUnsupportedExtension` - Extension violations
- ✅ `testInvalidFileExceptionImageTooSmall` - Dimension violations (min)
- ✅ `testInvalidFileExceptionImageTooLarge` - Dimension violations (max)
- ✅ `testStorageExceptionIsThrowable` - Exception interface compliance
- ✅ `testAllExceptionsImplementInterface` - Interface implementation
- ✅ `testAdapterExceptionFailedToReadLocalFile` - `AdapterException::failedToReadLocalFile()` includes file path
- ✅ `testAdapterExceptionFailedToCopyFile` - `AdapterException::failedToCopyFile()` includes both source and dest paths

### 7. Configuration Tests (9 tests)

#### StorageConfigTest (3 tests)
- ✅ `testCreatesLocalStorageConfig` - Local storage configuration
- ✅ `testCreatesDigitalOceanSpacesConfig` - DO Spaces configuration
- ✅ `testFromEnvWithLocalDriver` - Environment-based configuration
- ✅ `testFromEnvWithDigitalOceanSpaces` - DO Spaces from env
- ✅ Missing env variables throw ConfigurationException
- ✅ Configuration properties are readonly

## Key Features Tested

### ✅ MIME Type Validation (Security Feature) - **NEW**
The Storage Module validates **actual file type** by inspecting file content (magic bytes), not just extension:

```
❌ Before: virus.jpg (executable) → ❌ ACCEPTED (vulnerable!)
✅ After:  virus.jpg (executable) → ✅ REJECTED (secure!)
```

**How it Works:**
- Reads file header (magic bytes) to determine true type
- Detects: JPEG, PNG, GIF, WebP, BMP, ICO images
- Detects: MP4, WebM, AVI, MOV videos
- Rejects: Executables, scripts, unknown types
- Defense-in-depth: Works alongside extension validation

**Benefits:**
- 🛡️ Prevents file spoofing attacks
- 🔐 Secure by default - no configuration needed
- ⚡ Fast - magic byte detection is O(1)
- 🎯 Catches attacks that bypass extension checks

### ✅ Semantic Filename Generation (Core Feature)
The Storage Module preserves **business context** in filenames for traceability:

```
Before (generic):  image-abc123def456.jpg
After (semantic):  product-premium-tier-abc123def456.jpg
```

**Benefits:**
- 🎯 Traceability: Know what entity each file belongs to
- 📁 Organization: Semantically grouped storage
- 📋 Audit trails: Meaningful context for compliance
- 🐛 Debugging: Easy to identify related files

### ✅ Flexible Validation
- Extension validation (customizable, case-insensitive)
- Size validation (bytes, supports 0-500MB+ files)
- Image dimension constraints (min/max width/height)
- Pluggable validator system

### ✅ Configuration Management
- Environment-based configuration
- Support for multiple storage adapters (local, DO Spaces)
- Default values and fallbacks
- Readonly configuration objects

## Test Infrastructure

### Bootstrap (tests/bootstrap.php)
- Loads Composer autoloader
- Registers test namespace
- Creates temporary directories
- Configurable fixture paths

### Base Test Class (tests/Unit/StorageModuleTestCase.php)
- Creates mock uploaded files
- Manages temporary directories
- Cleanup after each test

### Test Data
- **Temporary directories**: Automatically created and cleaned up
- **Mock files**: JPEG, MP4, and other formats
- **GD-based images**: Real image generation for dimension tests

## Testing Custom Configurations

If implementing `StorageConfigInterface` for custom multi-bucket setups:

### Test Pattern

```php
// tests/Config/Storage/PublicStorageConfigTest.php
final class PublicStorageConfigTest extends TestCase {
    public function testImplementsInterface(): void {
        $config = new PublicStorageConfig();
        $this->assertInstanceOf(StorageConfigInterface::class, $config);
    }
    
    public function testReturnsDOSpacesDriver(): void {
        $config = new PublicStorageConfig();
        $this->assertEquals('do_spaces', $config->getDriver());
    }
    
    public function testReturnsDOSpacesConfig(): void {
        $_ENV['PUBLIC_BUCKET_KEY'] = 'test-key';
        $_ENV['PUBLIC_BUCKET_SECRET'] = 'test-secret';
        // ... set other env vars
        
        $config = new PublicStorageConfig();
        $doSpaces = $config->getDoSpacesConfig();
        
        $this->assertNotNull($doSpaces);
        $this->assertEquals('public-bucket', $doSpaces->bucket);
        $this->assertEquals('public-read', $doSpaces->acl);
    }
}
```

### Testing StorageManager

```php
// tests/Services/StorageManagerTest.php
final class StorageManagerTest extends TestCase {
    public function testReturnsDifferentAdaptersForDifferentDisks(): void {
        $manager = new StorageManager($paths, [
            'public' => new PublicStorageConfig(),
            'private' => new PrivateStorageConfig(),
        ]);
        
        $publicAdapter = $manager->disk('public');
        $privateAdapter = $manager->disk('private');
        
        // Both should implement interface but may be different adapter instances
        $this->assertInstanceOf(StorageAdapterInterface::class, $publicAdapter);
        $this->assertInstanceOf(StorageAdapterInterface::class, $privateAdapter);
    }
    
    public function testCachesAdapterInstances(): void {
        $manager = new StorageManager(...);
        
        $adapter1 = $manager->disk('public');
        $adapter2 = $manager->disk('public');
        
        // Should return same instance (cached)
        $this->assertSame($adapter1, $adapter2);
    }
}
```

---

## CI/CD Integration Example

When this module becomes standalone:

```yaml
# GitHub Actions
- name: Run Storage Module Tests
  run: |
    cd Modules/Storage
    ../../vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/Unit

- name: Upload Coverage
  uses: codecov/codecov-action@v3
  with:
    files: ./Modules/Storage/.phpunit.cache/code-coverage/index.html
```

## Troubleshooting

### Tests Not Found
```bash
# Ensure you're in the correct directory and using the bootstrap
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php Modules/Storage/tests/Unit
```

### Memory Issues with GD Library
The `ImageDimensionsValidator` tests create real JPEG images using GD. If you encounter memory issues:
- Run individual test suites separately
- Increase PHP memory limit: `php -d memory_limit=256M vendor/bin/phpunit ...`

### PHPUnit Configuration
This module includes its own PHPUnit configuration but uses the bootstrap parameter for compatibility with different PHPUnit versions.

## Future Enhancements

- [ ] Integration tests with actual storage adapters
- [ ] Performance benchmarks
- [x] S3/DO Spaces storage adapter tests (DOSpacesStorageAdapterTest — 12 tests)
- [ ] Concurrent upload stress tests
- [ ] Large file handling (GB+)

## Questions?

For test-related questions or to add more tests:
1. Review the test files in `tests/Unit/`
2. Follow the existing test patterns
3. Ensure new tests include both positive and negative cases
4. Maintain >90% code coverage
