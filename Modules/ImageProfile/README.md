# maatify/image-profile

A reusable, framework-agnostic image-profile definition and validation package.

---

## Table of Contents

- [Purpose](#purpose)
- [Requirements](#requirements)
- [Installation](#installation)
- [Core Concepts](#core-concepts)
- [Database Schema](#database-schema)
- [Providers](#providers)
- [Validation Flow](#validation-flow)
- [Public Entry Point](#public-entry-point)
- [Composition Helper](#composition-helper)
- [Error Handling](#error-handling)
- [Adapters](#adapters)
- [Storage](#storage)
- [Extension Strategy](#extension-strategy)
- [No-Array Contract](#no-array-contract)
- [Design Rules](#design-rules)
- [Versioning](#versioning)
- [License](#license)

---

## Purpose

`maatify/image-profile` is responsible for:

- defining reusable, named image validation profiles
- validating uploaded images against profile rules
- exposing a clean, typed validation result
- keeping image rules configurable and extensible without coupling to any HTTP framework

The package is **not** responsible for:

- storage engines or CDN delivery
- image resizing, optimization, or thumbnail generation
- framework HTTP lifecycle or direct `$_FILES` handling
- admin UI or CRUD controllers

Optional processing primitives may exist in the package for future extension,
but they are intentionally **not** part of the stable v1 validation path.

---

## Requirements

- PHP `^8.1`
- `ext-fileinfo` (for MIME detection in `NativeImageMetadataReader`)
- `ext-gd` (optional — only required by `TestImageFactory` in tests)
- No runtime dependencies beyond PHP itself

Optional project dependencies:

- `psr/http-message` — required to use `SlimUploadedFileAdapter`
- `aws/aws-sdk-php` — required to use `DoSpacesImageStorage`

---

## Installation

```bash
composer require maatify/image-profile
```

---

## Core Concepts

### Image Profile

An `ImageProfile` is a reusable, immutable rule set identified by a stable business `code` such as `category_app_image`, `product_thumbnail`, or `homepage_banner`.

```php
use Maatify\ImageProfile\Entity\ImageProfile;
use Maatify\ImageProfile\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection;

$profile = new ImageProfile(
    id:                1,
    code:              'product_thumbnail',
    displayName:       'Product Thumbnail',
    minWidth:          100,
    minHeight:         100,
    maxWidth:          2000,
    maxHeight:         2000,
    maxSizeBytes:      1_048_576, // 1 MB
    allowedExtensions: new AllowedExtensionCollection('jpg', 'jpeg', 'png', 'webp'),
    allowedMimeTypes:  new AllowedMimeTypeCollection('image/jpeg', 'image/png', 'image/webp'),
    isActive:          true,
    notes:             null,
);
```

Any `null` dimension bound disables that rule. Empty extension/MIME collections disable those restrictions entirely.

### Image File Input

`ImageFileInputDTO` is a neutral, framework-agnostic carrier of the upload data.

```php
use Maatify\ImageProfile\DTO\ImageFileInputDTO;

$input = new ImageFileInputDTO(
    originalName:   'banner.webp',
    temporaryPath:  '/tmp/phpXYZ123',
    clientMimeType: 'image/webp',   // client hint — not trusted for validation
    sizeBytes:      524288,
);
```

### Validation Result

`ImageValidationResultDTO` is the typed outcome of a validation call.

```php
$result->isValid()         // bool
$result->profileCode       // string
$result->metadata          // ?ImageMetadataDTO
$result->errors            // ImageValidationErrorCollectionDTO
$result->warnings          // ImageValidationWarningCollectionDTO
```

---

## Database Schema

```sql
CREATE TABLE `image_profiles` (
    `id`                 INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `code`               VARCHAR(64)    NOT NULL,
    `display_name`       VARCHAR(128)   DEFAULT NULL,
    `min_width`          INT UNSIGNED   DEFAULT NULL,
    `min_height`         INT UNSIGNED   DEFAULT NULL,
    `max_width`          INT UNSIGNED   DEFAULT NULL,
    `max_height`         INT UNSIGNED   DEFAULT NULL,
    `max_size_bytes`     BIGINT UNSIGNED DEFAULT NULL,
    `allowed_extensions` VARCHAR(255)   DEFAULT NULL,
    `allowed_mime_types` TEXT           DEFAULT NULL,
    `is_active`          TINYINT(1)     NOT NULL DEFAULT 1,
    `notes`              TEXT           DEFAULT NULL,
    `created_at`         DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_image_profiles_code` (`code`),
    KEY `idx_image_profiles_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`allowed_extensions` and `allowed_mime_types` are stored as comma-separated strings (e.g. `"jpg,png,webp"` and `"image/jpeg,image/png,image/webp"`). They are parsed automatically by `AllowedExtensionCollection::fromDelimitedString()` and `AllowedMimeTypeCollection::fromDelimitedString()`.

The full schema file with comments is at `src/Infrastructure/Schema/image_profiles.sql`.

---

## Providers

A provider is responsible for loading an `ImageProfile` by its `code`. The core validator never touches the database directly.

### Array Provider (testing / config-based)

```php
use Maatify\ImageProfile\Provider\ArrayImageProfileProvider;

$provider = new ArrayImageProfileProvider(
    new ImageProfile(code: 'product_thumbnail', /* ... */),
    new ImageProfile(code: 'homepage_banner',   /* ... */),
);

$profile = $provider->findByCode('product_thumbnail'); // ?ImageProfile
$all     = $provider->listAll();                       // ImageProfileCollectionDTO
$active  = $provider->listActive();                    // ImageProfileCollectionDTO
```

### PDO Provider (database-backed)

```php
use Maatify\ImageProfile\Infrastructure\Persistence\PDO\PdoImageProfileProvider;

$provider = new PdoImageProfileProvider($pdo);
// or with a custom table name:
$provider = new PdoImageProfileProvider($pdo, 'custom_image_profiles');

$profile = $provider->findByCode('product_thumbnail'); // ?ImageProfile
$all     = $provider->listAll();                       // ImageProfileCollectionDTO
$active  = $provider->listActive();                    // SQL-level filter
```

`findByCode` returns `null` for a missing code — it never throws.
`findByCode` does **not** filter by `is_active` — that is the validator's responsibility.

---

## Validation Flow

```php
use Maatify\ImageProfile\Reader\NativeImageMetadataReader;
use Maatify\ImageProfile\Validator\ImageProfileValidator;

$provider  = new PdoImageProfileProvider($pdo);
$reader    = new NativeImageMetadataReader();
$validator = new ImageProfileValidator($provider, $reader);

$input = new ImageFileInputDTO(
    originalName:   'thumbnail.png',
    temporaryPath:  '/tmp/phpABC',
    clientMimeType: 'image/png',
    sizeBytes:      204800,
);

$result = $validator->validateByCode('product_thumbnail', $input);

if ($result->isValid()) {
    // proceed to storage
} else {
    foreach ($result->errors as $error) {
        echo $error->code->value . ': ' . $error->message . PHP_EOL;
    }
}
```

The validator short-circuits only on infrastructure failures (missing profile, missing/unreadable file, unreadable metadata). For rule failures (mime, extension, dimensions, size) it collects **all** errors before returning.

---

## Public Entry Point

For most consumers, use `ImageProfileValidationService` as the neutral module boundary.

```php
use Maatify\ImageProfile\Service\ImageProfileValidationService;

$service = ImageProfileValidationService::compose($provider, $reader);

$result = $service->validateByCode('product_thumbnail', $input);
```

This service intentionally exposes only profile lookup/list + validation behavior.
It does not include controller, upload, or storage orchestration.

---

## Composition Helper

Use `ImageProfileComposition` for framework-agnostic wiring guidance.

### Compose from explicit dependencies

```php
use Maatify\ImageProfile\Bootstrap\ImageProfileComposition;

$service = ImageProfileComposition::fromProvider($provider, $reader);
```

### Compose from PDO (ready-to-use path)

```php
use Maatify\ImageProfile\Bootstrap\ImageProfileComposition;

$service = ImageProfileComposition::fromPdo($pdo, 'image_profiles');
```

---

## Error Handling

### Validation errors — returned, never thrown

| Code | Meaning |
|---|---|
| `profile_not_found` | No profile exists for the given code |
| `profile_inactive` | Profile exists but is disabled |
| `file_not_found` | Temporary file path does not exist |
| `file_not_readable` | File exists but cannot be read |
| `metadata_unreadable` | Metadata extraction failed |
| `mime_not_allowed` | Detected MIME type is not in the allowed list |
| `extension_not_allowed` | Detected extension is not in the allowed list |
| `width_too_small` | Image width is below the profile minimum |
| `height_too_small` | Image height is below the profile minimum |
| `width_too_large` | Image width exceeds the profile maximum |
| `height_too_large` | Image height exceeds the profile maximum |
| `file_too_large` | File size exceeds the profile maximum |

These codes are defined in `ValidationErrorCodeEnum` and will not change between minor versions.

### Exceptions — thrown for infrastructure and API misuse

| Exception | When |
|---|---|
| `ImageProfileNotFoundException` | Provider used directly and code not found |
| `InvalidImageInputException` | DTO constructed with invalid values, or upload error in adapter |
| `ImageMetadataReadException` | `finfo` / `getimagesize` failed on a file |
| `ImageProfileException` | Base class — catch this to handle all package exceptions |

---

## Adapters

Adapters convert framework-specific upload objects into `ImageFileInputDTO`. They live outside `src/` because the core must remain framework-agnostic.

### Slim / PSR-7 adapter

Requires `psr/http-message` in your project.

```php
use Maatify\ImageProfile\Adapter\SlimUploadedFileAdapter;

$uploadedFile = $request->getUploadedFiles()['image'];
$input        = SlimUploadedFileAdapter::toInputDTO($uploadedFile);
```

### Native PHP `$_FILES` adapter

No external dependencies.

```php
use Maatify\ImageProfile\Adapter\NativePhpUploadAdapter;

// From a specific field name:
$input = NativePhpUploadAdapter::fromSuperGlobal('image');

// Or from an already-fetched entry:
$input = NativePhpUploadAdapter::fromFilesEntry($_FILES['image']);
```

Both adapters throw `InvalidImageInputException` for any `UPLOAD_ERR_*` code other than `UPLOAD_ERR_OK`.

---

## Storage

Storage implementations live outside `src/`. The core validator has no storage dependency.

### DigitalOcean Spaces

Requires `aws/aws-sdk-php` in your project.

```php
use Aws\S3\S3Client;
use Maatify\ImageProfile\Storage\DoSpacesImageStorage;

$s3 = new S3Client([
    'version'     => 'latest',
    'region'      => 'fra1',
    'endpoint'    => 'https://fra1.digitaloceanspaces.com',
    'credentials' => [
        'key'    => $_ENV['DO_SPACES_KEY'],
        'secret' => $_ENV['DO_SPACES_SECRET'],
    ],
]);

$storage = new DoSpacesImageStorage(
    client:     $s3,
    bucket:     $_ENV['DO_SPACES_BUCKET'],
    cdnBaseUrl: $_ENV['DO_SPACES_CDN_URL'], // e.g. https://cdn.example.com
);
```

### Complete upload flow

```php
// 1. Adapt
$input = SlimUploadedFileAdapter::toInputDTO($request->getUploadedFiles()['image']);

// 2. Validate
$result = $validator->validateByCode('category_app_image', $input);

if (! $result->isValid()) {
    // return validation errors to the client
    return $response->withStatus(422);
}

// 3. Store
$stored = $storage->store(
    localPath:  $input->temporaryPath,
    remotePath: 'images/categories/' . uniqid() . '.webp',
);

// 4. Persist to database
// save $stored->publicUrl, $stored->remotePath, $stored->mimeType, $stored->sizeBytes
```

### StoredImageDTO fields

| Field | Type | Description |
|---|---|---|
| `publicUrl` | `string` | Full public URL (CDN or direct) |
| `remotePath` | `string` | Path inside the bucket — used for delete |
| `disk` | `string` | Backend identifier e.g. `do-spaces` |
| `sizeBytes` | `int` | File size as confirmed by the local file |
| `mimeType` | `string` | MIME type detected by `finfo` |

---

## Extension Strategy

### Processing and variants are extension scope (deferred)

Image processing primitives (resize, optimization, variant generation, preferred output hints)
are intentionally not part of the stable v1 validation entry path.
They are optional extension APIs and should not be treated as required dependencies
for core profile validation consumption.

### Adding a new provider

Implement `ImageProfileProviderInterface`:

```php
use Maatify\ImageProfile\Contract\ImageProfileProviderInterface;
use Maatify\ImageProfile\Entity\ImageProfile;

final class RedisImageProfileProvider implements ImageProfileProviderInterface
{
    public function findByCode(string $code): ?ImageProfile
    {
        // load from Redis, map to ImageProfile
    }
}
```

### Adding a new storage backend

Implement `ImageStorageInterface`:

```php
use Maatify\ImageProfile\Storage\ImageStorageInterface;
use Maatify\ImageProfile\Storage\StoredImageDTO;

final class S3ImageStorage implements ImageStorageInterface
{
    public function store(string $localPath, string $remotePath): StoredImageDTO { /* ... */ }
    public function delete(string $remotePath): void { /* ... */ }
}
```

### Adding a new metadata reader

Implement `ImageMetadataReaderInterface` and return an `ImageMetadataDTO`.

---

## No-Array Contract

No public API method in this package returns a raw PHP array for a collection. All collections use typed, iterable DTOs:

| Collection | Type |
|---|---|
| Validation errors | `ImageValidationErrorCollectionDTO` |
| Validation warnings | `ImageValidationWarningCollectionDTO` |
| Image profiles | `ImageProfileCollectionDTO` |
| Allowed extensions | `AllowedExtensionCollection` |
| Allowed MIME types | `AllowedMimeTypeCollection` |

All collection DTOs implement `IteratorAggregate` and `JsonSerializable`.

---

## Design Rules

- The core is **profile and validation focused** — not a generic upload service.
- The database is **optional** — profiles can be loaded from arrays or any custom provider.
- The validator works with a **neutral input DTO** — no framework upload objects in `src/`.
- Validation and processing are **always separate** — no merging of concerns.
- Stable business identifiers use `code`, not fragile display labels.
- No raw arrays on the public API — all collections are typed DTOs.
- Adapters and storage live **outside** `src/` — the core has zero framework or cloud SDK dependencies.

---

## Running Tests

```bash
composer install
./vendor/bin/phpunit                        # all suites
./vendor/bin/phpunit --testsuite Unit       # unit only
./vendor/bin/phpunit --testsuite Integration # integration only (SQLite in-memory)
```

Integration tests require only `ext-pdo` and `ext-pdo_sqlite` — no external database needed.
Validator and Reader unit tests require `ext-gd` for creating test images.

## Static Analysis

```bash
./vendor/bin/phpstan analyse
```

---

## Versioning

This package follows [Semantic Versioning](https://semver.org/).

- **Major** — breaking contract changes
- **Minor** — new capabilities, non-breaking
- **Patch** — bug fixes and internal improvements

---

## License

MIT — see [LICENSE](LICENSE).

---

## Author

[Maatify.dev](https://www.maatify.dev) — Mohamed Abdulalim
