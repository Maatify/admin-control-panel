# Changelog

All notable changes to the Maatify Storage Module are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.7.0] - 2026-05-20

### 🔐 Server-Side File Transfer & Presigned URL Support

Two new methods added to `StorageAdapterInterface` enabling post-payment AR fulfillment flows:
server-side copy of locally stored files into remote storage, and time-limited URL generation
for private objects.

#### Added

**`StorageAdapterInterface::storeFromPath()`**

```php
public function storeFromPath(string $localPath, string $destinationPath): StoredFile;
```

Copies a server-side file at an absolute filesystem path to the configured storage destination.
Unlike `store()`, this reads directly from a local path and **does not delete the source file**.

Use case: moving fulfilled draft files (e.g. customer-uploaded preparation images with
`image_storage_type = 2`) from protected local disk to private remote storage after payment.

**`StorageAdapterInterface::presign()`**

```php
public function presign(string $relativePath, int $expiresInSeconds = 3600): string;
```

Generates a time-limited URL for a stored file.

- **Private remote storage** (DO Spaces, `cdnUrl = null`): returns a presigned `GetObject` URL valid for `$expiresInSeconds`.
- **Public remote storage** (CDN-backed): returns the permanent CDN URL via `url()` — expiry ignored.
- **Local storage**: returns the local URL via `url()` — expiry ignored.

**`AdapterException` — two new named constructors**

```php
AdapterException::failedToReadLocalFile(string $path): self
AdapterException::failedToCopyFile(string $source, string $destination): self
```

#### Changed

**`DOSpacesStorageAdapter`**

- `storeFromPath()` implemented: opens source as `fopen('rb')` stream, calls `putObject`, closes stream in `finally` block to prevent resource leak.
- `presign()` implemented: uses `S3Client::createPresignedRequest('GetObject', ...)` for private buckets; falls back to `url()` for public buckets.
- `url()` fixed: when `cdnUrl` is `null` or `''`, now returns `''` instead of `'/' . $path`. Private objects have no public URL — callers must use `presign()`.
- `resolveMimeFromPath()` extended: added `gif`, `mp4`, `webm`, `mov`, `avi`, `mkv`, `mp3`, `wav`, `ogg` — required for AR media uploads.

**`LocalStorageAdapter`**

- `storeFromPath()` implemented: uses `copy()` (not `moveTo()`) to preserve the source file; creates destination directory if absent.
- `presign()` implemented: returns `url($relativePath)` — local files have no signing concept.

#### Breaking Changes

- ⚠️ `StorageAdapterInterface` has two new required methods (`storeFromPath`, `presign`).
  Any third-party implementation of the interface must add both methods.
  The two built-in adapters (`LocalStorageAdapter`, `DOSpacesStorageAdapter`) are already updated.
- ⚠️ `DOSpacesStorageAdapter::url()` behaviour changed for `cdnUrl = null`: previously returned `'/' . $path`, now returns `''`. Code that relied on the old path-style return for private adapters must switch to `presign()`.

---

## [1.6.1] - 2026-05-13

### 🐛 Bugfix — Range response body was not byte-limited

#### Fixed

**`LimitedResourceStream` (new — `src/Http/LimitedResourceStream.php`)**

`FileServeResponder` previously used `StreamFactory::createStreamFromResource()` which
wraps the raw file handle without any byte cap. PSR-7 emitters read such a stream until
EOF, so a `Range: bytes=0-99` request could leak the full file beyond the 100-byte budget
advertised in `Content-Length`.

Fixed by introducing `LimitedResourceStream`: a PSR-7 `StreamInterface` that wraps the
already-seeked resource and enforces an exact byte budget via `$remaining` tracking.

- `read(int $length)` — returns `min($length, $remaining)` bytes; returns `''` for `$length <= 0`
- `eof()` — `true` once remaining reaches 0 or underlying resource hits EOF
- `getSize()` — returns the byte limit (the range window), not the file size
- Not seekable / not writable — forward-only, serving only

**`FileServeResponder`** — `StreamFactoryInterface` dependency removed

`createStreamFromResource()` replaced by `new LimitedResourceStream($result->stream, $result->contentLength)`.
`StreamFactoryInterface` is no longer injected — `FileServeResponder` now requires only `ResponseFactoryInterface`.

**DI registrations updated** — `StreamFactoryInterface` removed from `FileServeResponder` closures in
`StorageBindings` and `MediaServiceProvider`.

#### Breaking Changes
- ✅ None — `FileServeResponder` constructor signature changed (one fewer parameter) but the class
  was newly added in 1.6.0 with no prior users.

---

## [1.6.0] - 2026-05-13

### 📡 Local File Streaming (FileServeService)

Added PHP-based file streaming for private files stored outside the web root.
Returns a `FileServeResult` DTO — the controller builds the PSR-7 response.
Supports HTTP Range requests (RFC 7233) for video/audio seeking.

#### Added

**`FileServeResult` DTO**
- `stream` — open file handle positioned at `rangeStart` (controller closes it)
- `mimeType` — detected via magic bytes, falls back to extension map
- `size` — total file size in bytes (for `Content-Range` header)
- `eTag` — `md5(mtime + size)` cache token (no file reading required)
- `lastModified` — `DateTimeImmutable` for `Last-Modified` header
- `isPartial` — `true` = 206 Partial Content, `false` = 200 OK
- `rangeStart` / `rangeEnd` / `contentLength` — byte offsets for `Content-Range`

**`FileServeService`**
```php
public function serve(string $path, ?string $rangeHeader = null): FileServeResult
```
- Path validation (same rules as `FileReaderService` — traversal, null bytes, etc.)
- File existence + readability check
- MIME detection via `finfo` → `mime_content_type` → extension fallback
- Range parsing — supports `bytes=0-999`, `bytes=500-`, `bytes=-500`
- `fopen` + `fseek` — stream opens and seeks in microseconds regardless of file size
- Local-only: DO Spaces files are served via CDN / presigned URLs

**`AdapterException::failedToOpenFile()`** — named constructor for stream open failures

**DI Registration**
- `StorageBindings::register()` — auto-registers `FileServeService` for single-disk local mode
- `MediaServiceProvider::registerServe(string $basePath)` — explicit registration for multi-disk

#### Example Usage

```php
// Controller (your responsibility: auth + response building)
$result = $fileServeService->serve($path, $request->getHeaderLine('Range'));

$response = $responseFactory->createResponse($result->isPartial ? 206 : 200)
    ->withHeader('Content-Type',   $result->mimeType)
    ->withHeader('Content-Length', (string) $result->contentLength)
    ->withHeader('Accept-Ranges',  'bytes')
    ->withHeader('ETag',           $result->eTag)
    ->withHeader('Last-Modified',  $result->lastModified->format('D, d M Y H:i:s \G\M\T'))
    ->withHeader('Content-Range',  $result->isPartial
        ? "bytes {$result->rangeStart}-{$result->rangeEnd}/{$result->size}"
        : '')
    ->withBody(new LimitedResourceStream($result->stream, $result->contentLength));
```

#### Multi-Disk Registration

```php
// Single local disk
MediaServiceProvider::register($builder, $projectRoot);
MediaServiceProvider::registerServe($builder, '/var/www/storage/private');

// Multiple local disks — instantiate directly in controller
$imageServe = new FileServeService('/var/www/storage/private/images');
$videoServe = new FileServeService('/var/www/storage/private/videos');
```

#### Breaking Changes
- ✅ None — purely additive.

---

## [1.5.0] - 2026-05-11

### 🗑️ File Deletion Support

Added `delete()` to the full service layer, completing the upload/delete lifecycle for all media types.

#### Added

**Delete method across all upload services**
- `FileUploadService::delete(string $path): void` — core delete, delegates to adapter
- `ImageUploadService::delete(string $path): void`
- `ImageUploadPublicService::delete(string $path): void`
- `ImageUploadPrivateService::delete(string $path): void`
- `VideoUploadService::delete(string $path): void`
- `VideoUploadPublicService::delete(string $path): void`
- `VideoUploadPrivateService::delete(string $path): void`
- `AudioUploadService::delete(string $path): void`
- `AudioUploadPublicService::delete(string $path): void`
- `AudioUploadPrivateService::delete(string $path): void`

#### How It Works

The call chain mirrors the upload chain exactly:

```
ImageUploadPublicService::delete()
  → ImageUploadService::delete()
    → FileUploadService::delete()
      → StorageAdapterInterface::delete()
        → LocalStorageAdapter   (unlink)
        → DOSpacesStorageAdapter (deleteObject)
```

Each Public/Private wrapper deletes from its own configured bucket because the adapter is resolved via DI — no extra configuration needed.

#### Example Usage

```php
// Upload an image
$storedFile = $imagePublicService->upload($file, 'products', customBaseName: 'product-slug');
// $storedFile->path = products/product-slug-abc123def456.jpg

// Later: delete it
$imagePublicService->delete($storedFile->path);
// Local: unlinks the file from disk
// DO Spaces: calls deleteObject on the configured bucket
```

#### Breaking Changes
- ✅ None — purely additive. All existing upload calls work unchanged.

#### Implementation Details
- Adapter layer (`LocalStorageAdapter`, `DOSpacesStorageAdapter`) already had `delete()` implemented
- `StorageAdapterInterface` already declared `delete()` in the contract
- This release threads the method up through the service layer only
- Non-existent files are silently ignored (both adapters)

---

## [1.4.0] - 2026-05-08

### 📖 Complete File Reading Module (Reader Services)

Comprehensive file reading capability matching the Upload architecture with 6 reader services (Image/Video/Audio × Public/Private).

#### Added

**Reader Services (Complete Symmetry with Upload)**
- 6 new reader services: `ImageReaderPublicService`, `ImageReaderPrivateService`, `VideoReaderPublicService`, `VideoReaderPrivateService`, `AudioReaderPublicService`, `AudioReaderPrivateService`
- Each service mirrors its corresponding upload service
- Supports both local filesystem and DigitalOcean Spaces

**Reader Adapter Interfaces**
- `ReaderAdapterInterface`: Unified contract for reading files across storage backends
- `LocalStorageReader`: Read from local filesystem with relative paths
- `DOSpacesStorageReader`: Read from DigitalOcean Spaces with presigned URLs

**File Access & Metadata DTOs**
- `FileAccessResult`: Encapsulates access URL, path, expiration, signing status
- `FileMetadata`: File size, content type, last modified, ETag for auditing

**Three Core Reader Methods**
- `getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult` - Get access URL/path for file
- `exists(string $path): bool` - Check file existence (excludes directories)
- `getMetadata(string $path): FileMetadata` - Get file metadata (size, type, modified time)

**Security**
- Path validation prevents directory traversal, null bytes, encoded attacks
- Double-decode traversal detection (%252e%252e → %2e%2e → ..)
- Validates leading slash, backslashes, double slashes
- Distinguishes files from directories (is_file() vs file_exists())

**Unified Configuration**
- Reuses `StorageConfigs` for both upload and reader services
- `ReaderAdapterFactory` mirrors `StorageAdapterFactory` pattern
- Shared `AppPaths` fallback for local storage

**DI Container Registration**
- `MediaServiceProvider` registers all 6 reader services
- Symmetric registration with upload services
- Full integration with PHP-DI container

**Testing**
- 18 comprehensive tests for LocalStorageReaderTest
- Tests cover: access URL, existence checking, metadata retrieval, path validation
- Directory handling verification
- Double-encoded traversal attack tests
- All tests passing with zero regressions

#### Example Usage

```php
// Read file — URL format is adapter-dependent, not service-type-dependent
$imageReader = $container->get(ImageReaderPublicService::class);
$accessResult = $imageReader->getAccessUrl('products/image-abc123.jpg');

// DO Spaces adapter: presigned GetObject URL
// Local adapter:     relative application-controlled path
echo $accessResult->url;
echo $accessResult->isSigned;           // true = presigned S3 URL, false = local path
echo $accessResult->expirationSeconds;  // seconds until expiry (0 for local)

// Private reader — same API, adapter resolves the URL
$privateImageReader = $container->get(ImageReaderPrivateService::class);
$accessResult = $privateImageReader->getAccessUrl('user-5/documents/invoice.jpg');
echo $accessResult->url;        // adapter-resolved URL (already signed for DO Spaces)
echo $accessResult->isSigned;   // true for DO Spaces, false for Local
echo $accessResult->path;       // normalized storage key

// Check file existence
$exists = $imageReader->exists('products/image-abc123.jpg');  // true

// Get metadata for auditing
$metadata = $imageReader->getMetadata('products/image-abc123.jpg');
echo $metadata->sizeBytes;    // 245120
echo $metadata->contentType;  // image/jpeg
```

#### Architecture Harmony
- ✅ Upload pattern: `FileUploadService` → `ImageUploadService` → `ImageUploadPublicService`
- ✅ Read pattern: `FileReaderService` → `ImageReaderService` → `ImageReaderPublicService`
- ✅ Same factory pattern: `ReaderAdapterFactory` mirrors `StorageAdapterFactory`
- ✅ Same DI registration: 6 upload + 6 reader services in `MediaServiceProvider`
- ✅ Same configuration: `StorageConfigs` used for both

#### Exception Hierarchy Unified
- All Storage module exceptions now extend `StorageException`
- No parallel exception trees
- Consistent error handling across upload and reader

**Files Changed:**
- Added: 9 new service files (FileReaderService, 3 type services, 6 wrappers)
- Added: 2 new adapter files (LocalStorageReader, DOSpacesStorageReader)
- Added: 2 new DTO files (FileAccessResult, FileMetadata)
- Added: 1 factory file (ReaderAdapterFactory)
- Added: 18 tests for LocalStorageReaderTest
- Modified: MediaServiceProvider (added reader registration)
- Modified: Exception files (unified hierarchy)
- Modified: Documentation (README, MULTI_DISK_GUIDE, etc.)

#### Breaking Changes
- ✅ None! Reader is purely additive
- Upload services work exactly as before
- Existing code continues to work unchanged

#### Test Coverage
- New tests: 18 (LocalStorageReaderTest)
- Total tests: 130 (102 upload + 28 reader)
- All passing with zero regressions

---

## [1.3.0] - 2026-05-07

### 🎵 Audio Upload Support

Added comprehensive audio file upload support with the same security and semantic naming features.

#### Added

**AudioUploadService**
- New service for uploading audio files with sensible defaults
- Supports: MP3, WAV, OGG formats
- Same architecture as ImageUploadService and VideoUploadService
- MIME type validation via magic bytes (prevents audio file spoofing)
- Semantic basename support for business context (e.g., "podcast-episode-5", "dua-001")
- Optional size limit constraints

**MimeTypeValidator Audio Support**
- Added MP3 detection (ID3v2 tags and MPEG Audio Frame header)
- Added WAV detection (RIFF ... WAVE signature)
- Added OGG detection (OggS magic bytes)
- New static helper: `MimeTypeValidator::audioTypes()`

**Test Infrastructure**
- Added audio mock file helpers to StorageModuleTestCase:
  - `createMockMp3File()`
  - `createMockWavFile()`
  - `createMockOggFile()`
- Added comprehensive AudioUploadServiceTest (10+ tests)
- Added audio format validation tests in MimeTypeValidatorTest
- Extended magic bytes injection to audio formats

#### Test Coverage
- New test suite: AudioUploadServiceTest with comprehensive coverage
- New validator tests: Audio MIME type validation tests
- All Storage tests passing

#### Example Usage

```php
$audioService = $container->get(AudioUploadService::class);

// Upload podcast with semantic naming
$storedFile = $audioService->upload(
    $file,
    'podcasts',
    customBaseName: 'episode-15-understanding-quran'
);
// Result: $storedFile->path = podcasts/episode-15-understanding-quran-abc123def456.mp3
// Result: $storedFile->url = https://cdn.example.com/podcasts/episode-15-understanding-quran-abc123def456.mp3

// Upload with size limit
$storedFile = $audioService->upload(
    $file,
    'audiobooks',
    maxSizeBytes: 100 * 1024 * 1024  // 100MB
);
```

---

## [1.2.0] - 2026-05-07

### 🔐 Enhanced Security - MIME Type Validation

Added critical security layer to prevent file spoofing attacks.

#### Added

**MIME Type Validation (New Security Feature)**
- `MimeTypeValidator`: Validates actual file type by inspecting content (magic bytes)
- Enabled by default in `ImageUploadService` and `VideoUploadService`
- Prevents attacks like `virus.jpg` (executable disguised as image)
- Validates 20+ file types including:
  - Images: JPEG, PNG, WebP, GIF, BMP, ICO
  - Videos: MP4, WebM, AVI, MOV, Matroska

#### Why This Matters

**Before (Vulnerable):**
```php
$imageService->upload($file, 'products');
// ❌ Accepts virus.jpg (real executable file with .jpg extension)
```

**After (Secure):**
```php
$imageService->upload($file, 'products');
// ✅ Rejects virus.jpg (reads file header, detects executable)
```

#### Technical Details

- Uses magic bytes (file signatures) to determine true file type
- Reads first 512 bytes of file for fast detection
- Defense-in-depth: Multiple validation layers
  1. MIME type (content-based) - reads file magic bytes
  2. Extension (filename-based) - quick double-check
  3. Size validator - prevents DoS
  4. Image dimensions (optional) - prevents bombs and validates integrity
- Zero performance impact: Magic byte detection is O(1)

#### Test Coverage

Added 17 new tests for `MimeTypeValidator`:
- ✅ Validates JPEG, PNG, GIF, WebP, BMP, ICO images
- ✅ Validates MP4, WebM, AVI, MOV videos
- ✅ **Critical**: Rejects executables disguised as images
- ✅ **Critical**: Rejects Windows PE executables disguised as images
- ✅ **Critical**: Rejects shell scripts disguised as images
- ✅ Handles unknown file types gracefully
- ✅ Handles short files (< 4 bytes) safely

---

## [1.1.0] - 2026-05-07

### 🔧 Enhanced Extensibility - Multi-Configuration Support

Added support for complex projects with multiple storage configurations while maintaining simplicity for single-storage projects.

#### Added

**Extensible Configuration Architecture**
- `StorageConfigInterface`: New interface for custom storage configurations
- `StorageConfigInterface::getDriver()`: Get storage driver name
- `StorageConfigInterface::getLocalConfig()`: Get local storage configuration
- `StorageConfigInterface::getDoSpacesConfig()`: Get DigitalOcean Spaces configuration

**Improvements**
- `StorageAdapterFactory::create()`: Now accepts `StorageConfigInterface` instead of concrete `StorageConfig`
- Support for unlimited custom configurations (public, private, media, backups, cdn, etc.)
- Full backward compatibility - existing code continues to work unchanged

**Documentation**
- `EXTENSION_GUIDE.md`: Complete guide for implementing custom configurations
- Examples for 5-bucket setup with `StorageManager` pattern
- Step-by-step implementation guide for complex projects

#### Changed

- `StorageAdapterFactory`: Updated to work with interface instead of concrete class
- `StorageConfig`: Now implements `StorageConfigInterface`

#### Notes

✅ **100% Backward Compatible**: Default `StorageConfig::fromEnv()` works unchanged  
✅ **Zero Breaking Changes**: All existing code continues to function  
✅ **Optional Extension**: Only implement interface if you need multiple configurations  
✅ **Type-Safe**: Full interface contracts for IDE support  

**Migration**: No action needed. Use as-is or implement interface for advanced scenarios.

---

## [1.0.0] - 2026-05-07

### ✨ Initial Release - Production Ready

This is the first production release of the Storage Module as a **fully independent, standalone library**.

#### Added

**Core Services**
- `FileUploadService`: Core file upload service with pluggable validators
- `ImageUploadService`: Ready-to-use image upload with semantic naming support
- `VideoUploadService`: Ready-to-use video upload for large files

**Semantic Filename Generation** (Core Feature)
- Optional `$customBaseName` parameter for business context in filenames
- Automatic uniqueness via random suffix (`bin2hex(random_bytes(8))`)
- Sanitization of special characters (lowercase, alphanumeric + hyphens)
- Examples:
  - Product images: `product-awesome-product-v2-abc123def456.jpg`
  - Payment methods: `visa-mastercard-001-abc123def456.jpg`
  - Translations: `payment-method-123-lang-1-abc123def456.jpg`

**Flexible Validation System**
- `FileValidator` interface for pluggable validation
- `ExtensionValidator`: File type validation (case-insensitive)
- `SizeValidator`: File size limits (bytes)
- `ImageDimensionsValidator`: Image dimension constraints (min/max width/height)
- Support for custom validators

**Configuration Management**
- `StorageConfig`: Main configuration class
- `LocalStorageConfig`: Local filesystem storage configuration
- `DOSpacesConfig`: DigitalOcean Spaces configuration
- `ImageDimensions`: Image dimension constraints
- Environment-based configuration loading (`StorageConfig::fromEnv()`)

**Storage Adapters**
- `StorageAdapterInterface`: Adapter contract
- `LocalStorageAdapter`: Local filesystem storage
- `DOSpacesAdapter`: DigitalOcean Spaces (S3-compatible) storage

**Exception Hierarchy** (Maatify Standard Compliant)
- `StorageExceptionInterface`: Common interface for all storage exceptions
- `StorageException`: Base exception
- `ConfigurationException`: Configuration-related errors with named constructors:
  - `missingEnvVariable()`
  - `unsupportedDriver()`
  - `missingAdapterConfig()`
  - `failedToResolveProjectRoot()`
- `FileUploadException`: PHP upload errors with named constructors:
  - `fromErrorCode()`
  - `unreadableStream()`
- `InvalidFileException`: Validation errors with named constructors:
  - `missingFilename()`
  - `unsupportedExtension()`
  - `fileTooLarge()`
  - `imageTooSmall()`
  - `imageTooLarge()`
  - `invalidImage()`

**Dependency Injection**
- `StorageBindings`: DI Container registration for all services and adapters
- Seamless integration with Maatify framework DI system

**Documentation**
- `README.md`: Comprehensive guide with examples and use cases
- `TESTING.md`: Complete testing guide
- `usage_example.php`: Real-world usage patterns
- Inline PHP documentation (PHPDoc)

**Test Suite** (60 Tests - 100% Passing)
- Unit tests for all validators
- Integration tests for image and video upload services
- Exception tests covering all error scenarios
- Configuration tests for all adapters
- Semantic naming tests for business context preservation
- Edge case tests (large files, boundary conditions)
- Test infrastructure:
  - Bootstrap file for independent execution
  - Base test case with utilities
  - PHPUnit configuration
  - Mock file creation for testing

**Code Quality**
- PHPStan Level Max compliance (0 errors)
- Full type hints throughout
- readonly properties for immutability
- Proper namespace organization
- Module Building Standard compliance (sections 3, 5, 6, 12)

#### Semantic Naming Feature Details

**Why Semantic Naming?**
- Filenames include business context (product slug, payment method code, etc.)
- Improves traceability in large storage systems
- Supports audit trails and compliance requirements
- Helps organize files meaningfully
- Makes debugging easier

**Examples**

Before (Generic):
```
images/
├── image-abc123def456.jpg
├── image-def456ghi789.jpg
└── image-jkl012mno345.jpg
```

After (Semantic):
```
products/
├── product-premium-tier-abc123def456.jpg
├── product-basic-tier-def456ghi789.jpg
└── product-enterprise-tier-jkl012mno345.jpg

payment-methods/
├── visa-mastercard-001-abc123def456.jpg
└── paypal-express-002-def456ghi789.jpg

translations/
├── payment-method-1-lang-1-abc123def456.jpg
├── payment-method-1-lang-2-def456ghi789.jpg
└── payment-method-2-lang-1-jkl012mno345.jpg
```

**Implementation**

Usage is optional and backwards-compatible:

```php
// Without semantic naming (auto-generated)
$storedFile = $imageService->upload($file, 'images');
// Result: $storedFile->path = images/image-abc123def456.jpg

// With semantic naming (business context)
$storedFile = $imageService->upload(
    $file,
    'images',
    customBaseName: 'product-awesome-product-v2'
);
// Result: $storedFile->path = images/product-awesome-product-v2-abc123def456.jpg
```

#### API Stability

All public APIs are stable and follow semantic versioning:

```php
// ImageUploadService::upload() signature
public function upload(
    UploadedFileInterface $file,
    string $subfolder,
    ?array $allowedExtensions = null,
    ?int $maxSizeBytes = null,
    ?ImageDimensions $dimensions = null,
    ?string $customBaseName = null
): StoredFile  // Returns both path and url

// VideoUploadService::upload() signature
public function upload(
    UploadedFileInterface $file,
    string $subfolder,
    ?array $allowedExtensions = null,
    ?int $maxSizeBytes = null,
    ?string $customBaseName = null
): StoredFile  // Returns both path and url
```

#### Null-Aware API Design

All optional parameters use `null` to indicate "use sensible default or skip":

```php
$imageService->upload($file, 'images')
// → Uses defaults (jpg, jpeg, png, webp), 5MB limit, no dimensions

$imageService->upload($file, 'images', ['png'])
// → Only PNG, 5MB limit

$imageService->upload($file, 'images', null, 10 * 1024 * 1024)
// → All defaults, 10MB limit

$imageService->upload($file, 'images', null, null, $dimensions)
// → All defaults, dimension constraints

$imageService->upload($file, 'images', customBaseName: 'slug')
// → All defaults, semantic naming
```

#### Testing Coverage

**60 Unit Tests** covering:

| Suite | Tests | Coverage |
|-------|-------|----------|
| Validators | 16 | ✅ All validation paths |
| Services | 30 | ✅ All upload scenarios + semantic naming |
| Exceptions | 11 | ✅ All error types and messages |
| Configuration | 3 | ✅ All adapters and env loading |

**Test Execution:**
```
PHPUnit 11.5.55
Runtime: PHP 8.4.4
Tests: 60
Assertions: 118
Time: 0.218 seconds
Memory: 12.02 MB
Result: ✅ OK (All tests passing)
```

#### Independent Module Structure

The module is completely independent and can be extracted as a standalone library:

- ✅ Self-contained tests (no project dependencies)
- ✅ Comprehensive documentation
- ✅ Clear file organization
- ✅ DI-agnostic (works with any container)
- ✅ No hardcoded project paths
- ✅ CI/CD ready

#### Known Limitations

1. **Image Dimension Validation** requires GD library (usually pre-installed)
2. **Large File Uploads** depend on server configuration (max_upload_size, memory_limit)
3. **DigitalOcean Spaces** requires valid credentials and network access

---

## [0.9.0] - Pre-Release (Internal Development)

### ⚠️ Note
This version existed during development but was never released. The 1.0.0 release represents the production-ready code.

#### What Was Developed
- Core validation system
- Service architecture
- Exception hierarchy
- Storage adapters
- Configuration management
- Test infrastructure

---

## Upgrade Guide

### From Pre-Release (0.9.0) to 1.0.0

No breaking changes. If you were using pre-release code:

1. Update any custom validators to implement `FileValidator`
2. Update exception imports (now in `Exception` namespace, not `Exceptions`)
3. Semantic naming is optional - existing code continues to work
4. Configuration structure unchanged

---

## Migration Notes

### For Existing Maatify Projects

The Storage Module is now **fully independent** but remains compatible with Maatify projects.

**Old code (still works):**
```php
// Using deprecated AdminKernel wrapper
$url = $this->fileUploadService->handleUpload($file, 'folder', 'slug');
```

**New code (recommended):**
```php
// Using new ImageUploadService with explicit parameter
$storedFile = $this->imageUploadService->upload(
    $file,
    'folder',
    customBaseName: 'slug'
);
// Returns StoredFile DTO with path and url properties
echo $storedFile->url;   // For display
echo $storedFile->path;  // For database storage
```

**Breaking Changes:**
- ❌ `Maatify\AdminKernel\Application\Services\FileUploadService` removed
- ❌ `handleUpload()` method no longer exists
- ✅ Migration path: Use `ImageUploadService::upload()` with `customBaseName` parameter

---

## Dependency Updates

| Dependency | Version | Reason |
|------------|---------|--------|
| PHP | 8.4+ | Type hints, readonly, named arguments |
| PSR-7 | Latest | UploadedFileInterface |
| Composer | Latest | Dependency management |

---

## Security Updates

**1.0.0 Release includes:**
- ✅ Extension validation (prevents executable uploads)
- ✅ Size validation (prevents DoS via large files)
- ✅ Dimension validation (prevents processing bombs)
- ✅ Random filename suffixes (prevents overwriting)
- ✅ Filename sanitization (removes special chars)
- ✅ No path traversal vulnerabilities

---

## Future Roadmap

### Planned for 1.6.0
- [ ] Stream-based large file uploads
- [ ] Resumable uploads
- [ ] Batch operations

### Planned for 1.2.0
- [ ] Image transformation (resize, crop)
- [ ] CDN integration
- [ ] Caching layer

### Planned for 2.0.0
- [ ] S3 native adapter
- [ ] Google Cloud Storage (GCS) adapter
- [ ] Azure Blob Storage adapter

---

## Support

For issues, feature requests, or questions:
1. Check [README.md](./README.md) for usage examples
2. Review [TESTING.md](./TESTING.md) for test patterns
3. See [usage_example.php](./usage_example.php) for more examples

---

## Contributors

### Original Development
- **Maatify Team**: Core design and implementation

### Testing & Documentation
- **QA Team**: Comprehensive test suite (60 tests)
- **Documentation Team**: Complete guides and examples

---

## License

See LICENSE file in the project root.

---

**Storage Module v1.0.0 is production-ready and can be used independently!** 🎉
