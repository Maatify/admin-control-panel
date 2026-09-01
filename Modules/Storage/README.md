# 📦 Maatify Storage Module

**A production-ready, standalone file storage management library for PHP applications.**

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue)](https://www.php.net)
[![Tests](https://img.shields.io/badge/Tests-130%2F130%20passing-green)](./TESTING.md)
[![Version](https://img.shields.io/badge/Version-1.6.0-blue)](./CHANGELOG.md)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%209-brightgreen)](./phpstan.neon.dist)

---

## ✨ Features

### 📤📥🗑️ **Complete File Lifecycle**
Upload, read, and delete files with a consistent API across all storage backends:

```php
// Upload with semantic naming
$storedFile = $imageService->upload(
    $file,
    'products',
    customBaseName: 'product-premium-tier'
);

// Later: Read the file's access URL and metadata
$imageReaderService = $container->get(ImageReaderPublicService::class);
$accessResult = $imageReaderService->getAccessUrl('products/product-premium-tier-abc123def456.jpg');
$metadata = $imageReaderService->getMetadata('products/product-premium-tier-abc123def456.jpg');

// Later: Delete the file
$imageService->delete($storedFile->path);

// Local private file: stream it to the browser
$result = $fileServeService->serve($storedFile->path, $request->getHeaderLine('Range'));
// → build PSR-7 response from $result (200 or 206 with Range support)
```

**Unified Pattern:**
- ✅ **Upload**: Store files with validation and semantic naming
- ✅ **Copy from server path**: Move server-side files to storage without an HTTP upload (`storeFromPath`)
- ✅ **Read**: Retrieve access URLs and metadata
- ✅ **Presign**: Generate time-limited URLs for private remote objects (`presign`)
- ✅ **Delete**: Remove files from local disk or DO Spaces
- ✅ **Serve**: Stream private local files to the browser (with Range support)
- ✅ **Symmetric API**: Upload and Reader services mirror each other (6 upload + 6 reader)

### 🎯 **Semantic Filename Generation**
Preserve business context in uploaded filenames for traceability and organization:

```php
// Instead of generic: image-abc123def456.jpg
// You get meaningful: product-premium-tier-abc123def456.jpg

$storedFile = $imageService->upload(
    $file,
    'products',
    customBaseName: 'product-premium-tier'  // Business context embedded
);

// Use the URL for display, path for storage
echo $storedFile->url;   // https://cdn.example.com/products/product-premium-tier-abc123def456.jpg
```

**Benefits:**
- 🔍 **Traceability**: Know what entity each file belongs to
- 📁 **Organization**: Semantically grouped storage
- 📋 **Audit Trails**: Meaningful context for compliance
- 🐛 **Debugging**: Easy to identify related files

### 🛡️ **Secure & Flexible Validation System**

**Built-in Security:**
- ✅ **MIME Type Validation**: Inspects file content (magic bytes), not just extension
- ✅ **Prevents File Spoofing**: Rejects `virus.jpg` (executable disguised as image)
- ✅ **Content-based Detection**: Reads actual file header to determine true type

**Customizable Validation:**

```php
// MIME type validation is ALWAYS enabled (security first)
// This prevents attacks like virus.jpg (executable disguised as image)
$imageService->upload($file, 'products');  // ✅ Secure by default

// Extension validation (customizable, in addition to MIME check)
$imageService->upload($file, 'products', 
    allowedExtensions: ['jpg', 'png', 'webp']
);

// Size constraints
$imageService->upload($file, 'products',
    maxSizeBytes: 5 * 1024 * 1024  // 5MB
);

// Image dimension constraints
$imageService->upload($file, 'products',
    dimensions: new ImageDimensions(
        minWidth: 100,
        minHeight: 100,
        maxWidth: 1920,
        maxHeight: 1080
    )
);
```

### 🔌 **Pluggable Validator Architecture**
Implement custom validators by extending `FileValidator`:

```php
final class CustomValidator implements FileValidator {
    public function validate(UploadedFileInterface $file): void {
        // Your validation logic
    }
}

$fileUploadService->upload($file, $path, 
    new ExtensionValidator(['jpg']),
    new SizeValidator(5 * 1024 * 1024),
    new CustomValidator()  // Add custom validators
);
```

### 📦 **Multiple Storage Adapters**

```php
// Local filesystem
$config = new StorageConfig(
    driver: 'local',
    local: new LocalStorageConfig(
        basePath: '/storage/uploads',
        baseUrl: '/uploads'
    )
);

// DigitalOcean Spaces (S3-compatible)
$config = new StorageConfig(
    driver: 'do_spaces',
    doSpaces: new DOSpacesConfig(
        key: 'your-key',
        secret: 'your-secret',
        endpoint: 'https://nyc3.digitaloceanspaces.com',
        bucket: 'my-bucket',
        region: 'nyc3',
        cdnUrl: 'https://my-bucket.nyc3.cdn.digitaloceanspaces.com'
    )
);

// Load from environment
$config = StorageConfig::fromEnv($_ENV);
```

### ⚡ **Automatic Uniqueness**
Every upload gets a unique random suffix, preventing collisions:

```
product-slug-abc123def456.jpg  (first upload)
product-slug-def456ghi789.jpg  (second upload - same slug, different file)
```

### 🔧 **Extensible Architecture**
Support for **simple projects** (single storage) and **complex projects** (5+ buckets):

```php
// Simple projects: Use default configuration
$config = StorageConfig::fromEnv($_ENV);

// Complex projects: Implement custom configurations
class PublicStorageConfig implements StorageConfigInterface { ... }
class PrivateStorageConfig implements StorageConfigInterface { ... }
class MediaStorageConfig implements StorageConfigInterface { ... }

// Zero code changes needed - module adapts to your needs!
```

**See [EXTENSION_GUIDE.md](./EXTENSION_GUIDE.md) for advanced usage patterns.**

---

## 📋 Requirements

- **PHP**: 8.4 or higher
- **PSR-7**: For UploadedFileInterface compatibility
- **Composer**: For dependency management

---

## 🚀 Installation

### As Part of Maatify Project
```bash
# Already included in Modules/Storage
composer install
```

### As Standalone Library (Future)
```bash
composer require maatify/storage
```

---

## 📖 Quick Start

### Reading Files (Retrieving Access URLs & Metadata)

```php
use Maatify\Storage\Services\ImageReaderService;

/** @var ImageReaderService $imageReader */
$imageReader = $container->get(ImageReaderService::class);

// Get access URL/path for the file
$accessResult = $imageReader->getAccessUrl('products/awesome-product-abc123def456.jpg');
echo $accessResult->url;       // Adapter-dependent:
                               // - DO Spaces: S3 presigned GetObject URL
                               // - Local: relative path for application access control
echo $accessResult->isSigned;  // true if URL is cryptographically signed, false otherwise

// Check if file exists
$exists = $imageReader->exists('products/awesome-product-abc123def456.jpg');  // true

// Get file metadata (size, type, modification time)
$metadata = $imageReader->getMetadata('products/awesome-product-abc123def456.jpg');
echo $metadata->sizeBytes;      // 245120 (bytes)
echo $metadata->contentType;    // image/jpeg
echo $metadata->lastModified;   // DateTimeImmutable object
```

### Basic Image Upload (All Defaults)

```php
use Maatify\Storage\Services\ImageUploadService;

/** @var ImageUploadService $imageService */
$imageService = $container->get(ImageUploadService::class);

// Upload with all defaults (jpg, jpeg, png, webp up to 5MB)
$storedFile = $imageService->upload($uploadedFile, 'products');

echo $storedFile->url;   // https://cdn.example.com/products/image-abc123def456.jpg
echo $storedFile->path;  // products/image-abc123def456.jpg
```

### Image Upload with Business Context

```php
// Include semantic naming for traceability
$storedFile = $imageService->upload(
    $uploadedFile,
    'products',
    customBaseName: $product->slug  // e.g., "awesome-product-v2"
);

echo $storedFile->url;   // https://cdn.example.com/products/awesome-product-v2-abc123def456.jpg
echo $storedFile->path;  // products/awesome-product-v2-abc123def456.jpg
```

### Advanced Image Upload with All Options

```php
$storedFile = $imageService->upload(
    $uploadedFile,
    'products',
    allowedExtensions: ['jpg', 'png'],  // Custom extensions
    maxSizeBytes: 10 * 1024 * 1024,     // 10MB limit
    dimensions: new ImageDimensions(
        minWidth: 400,
        minHeight: 300,
        maxWidth: 2000,
        maxHeight: 2000
    ),
    customBaseName: 'product-' . $product->id
);

// Access URL and path properties
echo $storedFile->url;    // Full CDN URL ready to display
echo $storedFile->path;   // Storage path for database storage
```

### Video Upload with Large Files

```php
use Maatify\Storage\Services\VideoUploadService;

/** @var VideoUploadService $videoService */
$videoService = $container->get(VideoUploadService::class);

// Upload large video with semantic naming
$storedFile = $videoService->upload(
    $uploadedFile,
    'courses',
    allowedExtensions: ['mp4', 'webm'],
    maxSizeBytes: 500 * 1024 * 1024,  // 500MB
    customBaseName: 'course-advanced-php-lesson-5'
);

echo $storedFile->url;   // https://cdn.example.com/courses/course-advanced-php-lesson-5-def456ghi789.mp4
echo $storedFile->path;  // courses/course-advanced-php-lesson-5-def456ghi789.mp4
```

### Using Core FileUploadService (Advanced)

```php
use Maatify\Storage\Services\FileUploadService;
use Maatify\Storage\Validators\ExtensionValidator;
use Maatify\Storage\Validators\SizeValidator;

/** @var FileUploadService $fileUploadService */
$fileUploadService = $container->get(FileUploadService::class);

// Full control with custom validators
$storedFile = $fileUploadService->upload(
    $uploadedFile,
    'documents/invoices',
    new ExtensionValidator(['pdf', 'docx']),
    new SizeValidator(50 * 1024 * 1024)  // 50MB
);

echo $storedFile->url;   // https://cdn.example.com/documents/invoices/invoice-abc123def456.pdf
echo $storedFile->path;  // documents/invoices/invoice-abc123def456.pdf
```

---

## 🔧 Configuration

### Environment Variables

```env
# Storage driver: 'local' or 'do_spaces'
STORAGE_DRIVER=local

# Local filesystem storage
LOCAL_BASE_PATH=/var/www/storage
LOCAL_BASE_URL=/uploads

# DigitalOcean Spaces
DO_SPACES_KEY=your-api-key
DO_SPACES_SECRET=your-api-secret
DO_SPACES_ENDPOINT=https://nyc3.digitaloceanspaces.com
DO_SPACES_BUCKET=my-bucket
DO_SPACES_REGION=nyc3
DO_SPACES_CDN_URL=https://my-bucket.nyc3.cdn.digitaloceanspaces.com
DO_SPACES_ACL=public-read
```

### Programmatic Configuration

```php
use Maatify\Storage\Config\StorageConfig;
use Maatify\Storage\Bootstrap\StorageBindings;
use DI\ContainerBuilder;

// From environment
$config = StorageConfig::fromEnv($_ENV);

// Or explicitly
$config = new StorageConfig(
    driver: 'local',
    local: new LocalStorageConfig(
        basePath: '/storage',
        baseUrl: '/uploads'
    )
);
```

---

## 🎯 Use Cases

### E-Commerce Product Images
```php
$storedFile = $imageService->upload(
    $file,
    'products',
    customBaseName: $product->slug
);
// Result: $storedFile->path = products/awesome-product-abc123def456.jpg
// Result: $storedFile->url = https://cdn.example.com/products/awesome-product-abc123def456.jpg
```

### Payment Method Icons
```php
$storedFile = $imageService->upload(
    $file,
    'payment-methods',
    customBaseName: $paymentMethod->code
);
// Result: $storedFile->path = payment-methods/visa-abc123def456.jpg
// Result: $storedFile->url = https://cdn.example.com/payment-methods/visa-abc123def456.jpg
```

### Language-Specific Content
```php
$storedFile = $imageService->upload(
    $file,
    'translations',
    customBaseName: "product-{$id}-lang-{$languageId}"
);
// Result: $storedFile->path = translations/product-123-lang-1-abc123def456.jpg
// Result: $storedFile->url = https://cdn.example.com/translations/product-123-lang-1-abc123def456.jpg
```

### Course Videos
```php
$storedFile = $videoService->upload(
    $file,
    'courses',
    customBaseName: "course-{$courseId}-lesson-{$lessonNumber}",
    maxSizeBytes: 500 * 1024 * 1024
);
// Result: $storedFile->path = courses/course-456-lesson-5-def456ghi789.mp4
// Result: $storedFile->url = https://cdn.example.com/courses/course-456-lesson-5-def456ghi789.mp4
```

### Webinar Recordings
```php
$storedFile = $videoService->upload(
    $file,
    'webinars',
    customBaseName: "webinar-{$date}-{$title}",
    maxSizeBytes: 1024 * 1024 * 1024  // 1GB
);
// Result: $storedFile->path = webinars/webinar-2024-03-15-keynote-jkl012mno345.mp4
// Result: $storedFile->url = https://cdn.example.com/webinars/webinar-2024-03-15-keynote-jkl012mno345.mp4
```

---

## 📊 Validation Rules

### Default Extensions

**ImageUploadService:**
- `jpg`, `jpeg`, `png`, `webp`

**VideoUploadService:**
- `mp4`, `webm`, `mov`, `avi`

### Default Size Limits

**ImageUploadService:**
- Max: 5 MB

**VideoUploadService:**
- Max: 500 MB (no limit if not specified)

### Custom Validation

```php
// Any validator implementing FileValidator
$imageService->upload($file, 'images',
    allowedExtensions: ['gif', 'svg'],
    maxSizeBytes: 2 * 1024 * 1024
);
```

---

## 🚨 Error Handling

### Exception Types

```php
use Maatify\Storage\Exception\{
    StorageException,
    ConfigurationException,
    FileUploadException,
    InvalidFileException
};

try {
    $url = $imageService->upload($file, 'products');
} catch (InvalidFileException $e) {
    // File validation failed (extension, size, dimensions)
    echo $e->getMessage();
} catch (FileUploadException $e) {
    // PHP upload error
    echo $e->getMessage();
} catch (ConfigurationException $e) {
    // Configuration issue (missing env vars, etc)
    echo $e->getMessage();
} catch (StorageException $e) {
    // Generic storage error
    echo $e->getMessage();
}
```

### Common Error Messages

```
"Invalid file extension "exe". Allowed: jpg, jpeg, png, webp."
"File size [2 MB] exceeds maximum allowed size [1 MB]."
"Image dimensions [800x600] below minimum [1920x1080]."
"Could not read uploaded file stream."
"Missing required environment variable: DO_SPACES_KEY"
```

---

## 📚 Full API Reference

### Reader Services (Image/Video/Audio × Public/Private)

All Reader services return `FileAccessResult` — URL format is **adapter-dependent**, not service-type-dependent:

- **DO Spaces adapter**: `FileAccessResult::url` is a presigned S3 `GetObject` URL (time-limited, `isSigned = true`)
- **Local adapter**: `FileAccessResult::url` is a relative application-controlled path (`isSigned = false`)
- `FileAccessResult::path` is always the normalized storage key
- `FileAccessResult::isSigned` indicates whether the URL is already cryptographically signed
- Upload `StoredFile::url` may be a CDN/public URL depending on adapter config — this is separate from reader access

**Public Readers** (ImageReaderPublicService, VideoReaderPublicService, AudioReaderPublicService):
- Use for publicly-accessible file buckets
- Return `FileAccessResult` — URL format determined by configured adapter

**Private Readers** (ImageReaderPrivateService, VideoReaderPrivateService, AudioReaderPrivateService):
- Use for restricted/authenticated file buckets
- Return `FileAccessResult` — URL format determined by configured adapter (DO Spaces returns presigned URL automatically)

#### Reader Method: `getAccessUrl()`

```php
$reader = $container->get(ImageReaderPublicService::class);

$result = $reader->getAccessUrl(
    path: 'products/image-abc123def456.jpg',  // Storage path
    expirationMinutes: 60                     // For private: expiration time
): FileAccessResult
```

**FileAccessResult properties:**
- `url`: String - access URL (adapter-dependent format: DO Spaces returns presigned S3 URL, Local returns relative path)
- `path`: String - storage path
- `isSigned`: Bool - whether URL is cryptographically signed (true for DO Spaces, false for Local)
- `expiresAt`: DateTimeImmutable - expiration timestamp
- `expirationSeconds`: Int - time-to-live in seconds

#### Reader Method: `exists()`

```php
$reader = $container->get(ImageReaderPublicService::class);

$exists = $reader->exists('products/image-abc123def456.jpg'): bool
```

Returns true only if file exists (directories return false).

#### Reader Method: `getMetadata()`

```php
$reader = $container->get(ImageReaderPublicService::class);

$metadata = $reader->getMetadata(
    path: 'products/image-abc123def456.jpg'
): FileMetadata
```

**FileMetadata properties:**
- `sizeBytes`: Int - file size in bytes
- `contentType`: ?String - MIME type (e.g., 'image/jpeg')
- `lastModified`: ?DateTimeImmutable - last modification time
- `eTag`: ?String - entity tag for change detection

---

### ImageUploadService

```php
public function upload(
    UploadedFileInterface $file,
    string $subfolder,
    ?array $allowedExtensions = null,    // null = defaults
    ?int $maxSizeBytes = null,           // null = no limit
    ?ImageDimensions $dimensions = null, // null = no constraint
    ?string $customBaseName = null       // null = auto-generate
): StoredFile

public function delete(string $path): void
```

**Returns StoredFile DTO:**
- `path`: String - storage key for database storage and Reader access
- `url`: String - resolved URL (CDN or relative path)

### VideoUploadService

```php
public function upload(
    UploadedFileInterface $file,
    string $subfolder,
    ?array $allowedExtensions = null,  // null = defaults
    ?int $maxSizeBytes = null,         // null = no limit
    ?string $customBaseName = null     // null = auto-generate
): StoredFile

public function delete(string $path): void
```

**Returns StoredFile DTO with `path` and `url` properties.**

### AudioUploadService

```php
public function upload(
    UploadedFileInterface $file,
    string $subfolder,
    ?array $allowedExtensions = null,  // null = defaults (mp3, wav, ogg)
    ?int $maxSizeBytes = null,         // null = no limit
    ?string $customBaseName = null     // null = auto-generate
): StoredFile

public function delete(string $path): void
```

**Supported formats:**
- **MP3**: MPEG Audio (FF FB header or ID3v2 tag - both real-world formats)
- **WAV**: Waveform Audio (RIFF + WAVE)
- **OGG**: Ogg Vorbis (OggS signature)

**Returns StoredFile DTO with `path` and `url` properties.**

### FileUploadService (Core)

```php
public function upload(
    UploadedFileInterface $file,
    string $destinationPath,
    FileValidator ...$validators
): StoredFile

public function delete(string $path): void
```

**Returns StoredFile DTO with `path` and `url` properties.**

### FileServeService (Local private files only)

```php
public function serve(string $path, ?string $rangeHeader = null): FileServeResult
```

Streams a private local file to the browser. Pass the `Range` header from the request
for video/audio seeking (RFC 7233). DO Spaces files do not use this service.

**FileServeResult properties:**
- `stream`: `resource` — open file handle seeked to `rangeStart`. Controller must close it.
- `mimeType`: `string` — detected via magic bytes (e.g. `video/mp4`)
- `size`: `int` — total file size in bytes
- `eTag`: `string` — cache validation token
- `lastModified`: `DateTimeImmutable` — for `Last-Modified` header
- `isPartial`: `bool` — `true` = 206 Partial Content, `false` = 200 OK
- `rangeStart` / `rangeEnd` / `contentLength`: `int` — byte range for `Content-Range` header

### StorageAdapterInterface — Low-Level Adapter Contract

All adapters (`LocalStorageAdapter`, `DOSpacesStorageAdapter`) implement the full interface:

```php
// Store an uploaded file (from HTTP request)
public function store(UploadedFileInterface $file, string $destinationPath): StoredFile;

// Copy a server-side file to storage (does NOT delete the source)
public function storeFromPath(string $localPath, string $destinationPath): StoredFile;

// Resolve the public URL for a stored relative path
public function url(string $relativePath): string;

// Generate a time-limited URL for a stored file
// — Private DO Spaces: presigned GetObject URL valid for $expiresInSeconds
// — Public DO Spaces / Local: returns permanent URL, expiry is ignored
public function presign(string $relativePath, int $expiresInSeconds = 3600): string;

// Delete a stored file (silently ignores non-existent files)
public function delete(string $path): void;
```

**`storeFromPath` use case — post-payment file promotion:**

```php
// Customer uploaded a draft image to local protected storage (image_storage_type=2).
// After payment is confirmed, promote it to private DO Space (image_storage_type=3):
$storedFile = $privateSpaceAdapter->storeFromPath(
    '/var/www/storage/protected/drafts/7/3/image-abc.jpg',
    'ar/7/42/img-15-image-abc.jpg'
);
// $storedFile->path = 'ar/7/42/img-15-image-abc.jpg'  ← store in DB
// $storedFile->url  = ''                               ← empty for private adapters
// Source file on disk is preserved for retry safety.
```

**`presign` use case — serving private AR images:**

```php
// At QR scan time, generate a 24-hour signed URL for private AR content:
$signedUrl = $privateSpaceAdapter->presign('ar/7/42/img-15-image-abc.jpg', 86400);
// Returns: https://space.fra1.digitaloceanspaces.com/ar/7/42/img-15-image-abc.jpg?X-Amz-...
```

**`url()` behaviour for private adapters:**

When a `DOSpacesStorageAdapter` is constructed with `cdnUrl: null` (private bucket),
`url()` returns `''`. There is no public CDN URL for private objects — use `presign()` instead.

---

## ✅ Testing

The module includes a comprehensive test suite:

```bash
# Run all tests
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php \
    Modules/Storage/tests/Unit --no-coverage

# Run with coverage report
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php \
    Modules/Storage/tests/Unit \
    --coverage-html=.phpunit.cache/code-coverage

# Run specific suite
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php \
    Modules/Storage/tests/Unit/Services
```

See [TESTING.md](./TESTING.md) for detailed testing guide.

---

## 🏗️ Architecture

```
src/
├── Services/
│   ├── FileUploadService.php              (Core upload, pluggable validators)
│   ├── ImageUploadService.php             (Upload - semantic naming)
│   ├── VideoUploadService.php             (Upload - large files)
│   ├── AudioUploadService.php             (Upload - audio formats)
│   ├── [Public/Private wrapper services]  (6 upload wrappers)
│   │
│   ├── FileReaderService.php              (Core reader, path validation)
│   ├── ImageReaderService.php             (Reader - image metadata)
│   ├── VideoReaderService.php             (Reader - video metadata)
│   ├── AudioReaderService.php             (Reader - audio metadata)
│   ├── [Public/Private reader services]   (6 reader wrappers)
│   │
│   └── FileServeService.php               (Stream local private files, Range support)
├── Validators/
│   ├── FileValidator.php           (Interface)
│   ├── ExtensionValidator.php
│   ├── SizeValidator.php
│   └── ImageDimensionsValidator.php
├── Config/
│   ├── StorageConfig.php
│   ├── LocalStorageConfig.php
│   ├── DOSpacesConfig.php
│   └── ImageDimensions.php
├── Contracts/
│   ├── StorageAdapterInterface.php  (Upload interface)
│   ├── ReaderAdapterInterface.php   (Read interface)
│   └── FileValidator.php
├── DTO/
│   ├── StoredFile.php              (Upload result: path + url)
│   ├── FileAccessResult.php        (Read result: url + expiration)
│   ├── FileMetadata.php            (Read result: size, type, modified)
│   └── FileServeResult.php         (Serve result: stream + headers + range)
├── Exception/
│   ├── StorageExceptionInterface.php (Base interface)
│   ├── StorageException.php          (Base class)
│   ├── FileUploadException.php       (Upload errors)
│   ├── InvalidFileException.php      (Validation errors)
│   ├── ReaderException.php           (Reader base)
│   ├── ReaderAdapterException.php    (Reader adapter errors)
│   ├── InvalidPathException.php      (Path validation)
│   ├── FileNotFoundException.php     (File not found)
│   ├── AdapterException.php          (General adapter)
│   └── ConfigurationException.php    (Config errors)
├── Adapters/
│   ├── LocalStorageAdapter.php       (Write to filesystem)
│   ├── DOSpacesStorageAdapter.php    (Write to DO Spaces)
│   ├── LocalStorageReader.php        (Read from filesystem)
│   └── DOSpacesStorageReader.php     (Read from DO Spaces)
├── Factory/
│   ├── StorageAdapterFactory.php     (Create upload adapters)
│   └── ReaderAdapterFactory.php      (Create reader adapters)
└── Bootstrap/
    ├── StorageBindings.php           (DI Container - single storage)
    └── MediaServiceProvider.php      (DI Container - 6 upload + 6 reader)
```

**Symmetric Design:**
- 2 adapters for uploading (Local, DOSpaces)
- 2 adapters for reading (Local, DOSpaces)
- 6 upload services (Image/Video/Audio × Public/Private)
- 6 reader services (Image/Video/Audio × Public/Private)
- Unified configuration system for both upload and read

**Design Principles:**
- ✅ **Separation of Concerns**: Services, validators, adapters are separate
- ✅ **Pluggable Architecture**: Add custom validators easily
- ✅ **Ready-to-Use Services**: ImageUploadService, VideoUploadService & AudioUploadService have sensible defaults
- ✅ **Null-Aware API**: Explicit null handling for flexibility
- ✅ **Named Constructors**: All exceptions use named constructors (Maatify standard)

---

## 📋 Maatify Module Building Standard Compliance

This module follows the **Maatify Module Building Standard**:

- ✅ **Section 6**: Named constructors for all exceptions
- ✅ **Section 12**: Services orchestrate, validators validate (separation)
- ✅ **Section 3**: Clear directory structure
- ✅ **Section 5**: Proper exception hierarchy with StorageExceptionInterface

---

## 🎓 Examples

### Real-World: Product Management System

```php
namespace App\Http\Controllers;

use Maatify\Storage\Services\ImageUploadService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProductImageController {
    public function __construct(
        private ImageUploadService $imageService
    ) {}

    public function upload(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface {
        $product = $this->getProduct($req);
        $file = $req->getUploadedFiles()['image'];

        try {
            // Semantic naming: filename includes product slug
            $imageUrl = $this->imageService->upload(
                $file,
                'products',
                dimensions: new ImageDimensions(
                    minWidth: 400,
                    minHeight: 300,
                    maxWidth: 2000,
                    maxHeight: 2000
                ),
                customBaseName: $product->slug
            );

            // Update database with URL
            $product->image_url = $imageUrl;
            $product->save();

            return $this->json->success($res, ['image_url' => $imageUrl]);
        } catch (InvalidFileException $e) {
            return $this->json->error($res, $e->getMessage(), 422);
        }
    }
}
```

**Result:** `products/awesome-product-abc123def456.jpg`
- ✅ Semantic naming for traceability
- ✅ Dimension validation
- ✅ Automatic uniqueness
- ✅ Error handling

---

## 🔄 Null Handling Guide

```php
// All these are valid and have clear semantics:

$imageService->upload($file, 'images')
// Uses: all defaults (jpg, jpeg, png, webp), 5MB limit, no dimensions

$imageService->upload($file, 'images', ['png'])
// Uses: only PNG, 5MB limit, no dimensions

$imageService->upload($file, 'images', null, 10 * 1024 * 1024)
// Uses: defaults, 10MB limit, no dimensions

$imageService->upload($file, 'images', null, null, $dimensions)
// Uses: defaults, no size limit, dimension constraints

$imageService->upload($file, 'images', customBaseName: 'product-slug')
// Uses: all defaults + semantic naming
```

**Philosophy:** `null` means "use sensible default or skip this constraint"

---

## 🚀 Deployment

### Local Development
```env
STORAGE_DRIVER=local
LOCAL_BASE_PATH=./storage/uploads
LOCAL_BASE_URL=/uploads
```

### Production - DigitalOcean Spaces
```env
STORAGE_DRIVER=do_spaces
DO_SPACES_KEY=${DO_SPACES_KEY}
DO_SPACES_SECRET=${DO_SPACES_SECRET}
DO_SPACES_ENDPOINT=https://nyc3.digitaloceanspaces.com
DO_SPACES_BUCKET=${DO_SPACES_BUCKET}
DO_SPACES_REGION=nyc3
DO_SPACES_CDN_URL=${DO_SPACES_CDN_URL}
DO_SPACES_ACL=public-read
```

---

## 📈 Performance Considerations

- **Filename Generation**: O(1) - constant time
- **Validation**: O(n) where n = file size (for size validation)
- **Image Dimension Validation**: Requires GD library, ~50-200ms per image
- **Large Files**: Supports 0-1GB+ (tested with 500MB videos)

---

## 🔐 Security

- ✅ **MIME Type Validation** (Enabled by default): Inspects actual file content (magic bytes) to detect true file type
  - Prevents attacks like `virus.jpg` (executable disguised as image)
  - Works by reading file header, not just checking extension
  - Uses industry-standard magic byte signatures
- ✅ **Extension Validation**: Double-checks file extension (fast layer)
- ✅ **Size Limits**: Prevents DoS via massive files
- ✅ **Dimension Validation**: Prevents processing bombs and validates image integrity
- ✅ **Random Suffixes**: Prevents file overwriting attacks
- ✅ **Sanitized Filenames**: Special characters removed to prevent injection
- ✅ **No Path Traversal**: Subfolder parameter validated
- ✅ **Defense in Depth**: Multiple validation layers ensure file safety

---

## 📝 License

This module is part of the Maatify framework. See LICENSE file in the project root.

---

## 🔧 Advanced: Multi-Configuration Setups

For projects requiring multiple storage configurations (e.g., public, private, media, backups, CDN):

### Simple Projects (Single Storage)
Use the default configuration - zero custom code needed:

```php
$config = StorageConfig::fromEnv($_ENV);
StorageBindings::register($container, APP_ROOT, $config);
```

### Complex Projects (Multiple Storages)
Implement `StorageConfigInterface` for custom configurations:

```php
// Implement interface for each storage scenario
class PublicStorageConfig implements StorageConfigInterface { ... }
class PrivateStorageConfig implements StorageConfigInterface { ... }
class MediaStorageConfig implements StorageConfigInterface { ... }

// Create a StorageManager to manage them
class StorageManager {
    public function disk(string $name): StorageAdapterInterface { ... }
}

// Use in controllers - wrap adapter in service for upload() with validation
$adapter = $storageManager->disk('public');
$imageService = new ImageUploadService(new FileUploadService($adapter));
$imageService->upload($file, 'images', customBaseName: 'product-slug');

$adapter = $storageManager->disk('private');
$fileService = new FileUploadService($adapter);
// FileUploadService builds path directly (no customBaseName parameter)
$semanticPath = 'documents/invoices/invoice-001-' . bin2hex(random_bytes(8)) . '.pdf';
$fileService->upload($file, $semanticPath);
```

**See [EXTENSION_GUIDE.md](./EXTENSION_GUIDE.md) for complete examples and patterns.**

### Key Benefits
- ✅ Supports unlimited configurations
- ✅ Each bucket can have different settings
- ✅ Zero module changes needed
- ✅ 100% backward compatible
- ✅ Type-safe with `StorageConfigInterface`

---

## 🤝 Contributing

To extend this module:

1. **Custom Validators**: Implement `FileValidator` interface
2. **Custom Adapters**: Implement `StorageAdapterInterface`
3. **Custom Configurations**: Implement `StorageConfigInterface` (for multi-storage setups)
4. **Tests**: Add tests to `tests/Unit/`
5. **Documentation**: Update README and TESTING.md

---

## 📞 Support

For issues or questions:
- Check [TESTING.md](./TESTING.md) for test examples
- Review examples in this README
- See [usage_example.php](./usage_example.php) for more patterns

---

## 🎯 Roadmap

- [ ] Stream-based large file uploads
- [ ] Resumable uploads
- [ ] Batch operations
- [ ] Image transformation (resize, crop)
- [ ] CDN integration
- [ ] S3 native adapter
- [ ] GCS (Google Cloud Storage) adapter

---

**Built with ❤️ to be standalone, testable, and production-ready.**

Current Version: **1.6.0** (See [CHANGELOG.md](./CHANGELOG.md))
