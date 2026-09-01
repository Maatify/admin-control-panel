# Storage Module - Implementation Plan v2.0

**Status:** 🚀 In Development  
**Last Updated:** 2025-05-07

---

## ⚖️ MODULE_BUILDING_STANDARD Compliance

This refactor **MUST** follow Maatify Module Building Standard v1:

### ✅ Already Compliant
- Namespace: `Maatify\Storage\`
- Standalone & extractable (PSR-4 autoload)
- PHPStan level: max

### 📝 Adjustments for Standard Compliance
- **Exception folder:** Rename `Exceptions/` → `Exception/` (per section 4)
- **Exception interface:** Create `StorageExceptionInterface` (per section 6)
- **All exceptions:** Implement interface + use named constructors (per section 6)
- **All exceptions:** Extend `\RuntimeException` (per section 6)
- **Bootstrap:** Explicit `/** @var Type */` annotations (per section 16)
- **PHPStan annotations:** Follow exact patterns (per section 19)

---

## 📋 Overview

Refactor Storage Module to support:
- ✅ Pluggable file validators
- ✅ Multiple storage adapters (Local, DO Spaces)
- ✅ Optional validation (extensions, size, dimensions)
- ✅ Separate services for Images and Videos
- ✅ Null-aware configuration (null = use defaults OR skip validation)

---

## 📂 File Structure

```
Modules/Storage/
├── src/
│   ├── Adapters/
│   │   ├── LocalStorageAdapter.php          (✓ Exists)
│   │   └── DOSpacesStorageAdapter.php       (✓ Exists)
│   │
│   ├── Bootstrap/
│   │   └── StorageBindings.php              (📝 Update)
│   │
│   ├── Config/
│   │   ├── DOSpacesConfig.php               (✓ Exists)
│   │   ├── LocalStorageConfig.php           (✓ Exists)
│   │   ├── StorageConfig.php                (✓ Exists)
│   │   └── ImageDimensions.php              (🆕 New)
│   │
│   ├── Contracts/
│   │   ├── FileValidator.php                (🆕 New Interface)
│   │   └── StorageAdapterInterface.php      (✓ Exists)
│   │
│   ├── Exception/                           (📁 Folder name per standard)
│   │   ├── StorageExceptionInterface.php    (🆕 New - per MODULE_BUILDING_STANDARD)
│   │   ├── InvalidFileException.php         (📝 Update - impl interface + named constructors)
│   │   ├── AdapterException.php             (📝 Update - impl interface + named constructors)
│   │   ├── ConfigurationException.php       (📝 Update - impl interface + named constructors)
│   │   └── FileUploadException.php          (📝 Update - impl interface + named constructors)
│   │
│   ├── Factory/
│   │   └── StorageAdapterFactory.php        (✓ Exists)
│   │
│   ├── Validators/                          (🆕 New Folder)
│   │   ├── ExtensionValidator.php           (🆕 New)
│   │   ├── SizeValidator.php                (🆕 New)
│   │   └── ImageDimensionsValidator.php     (🆕 New)
│   │
│   └── Services/
│       ├── FileUploadService.php            (📝 Refactor)
│       ├── ImageUploadService.php           (🆕 New)
│       └── VideoUploadService.php           (🆕 New)
│
├── IMPLEMENTATION_PLAN.md                   (📍 This file)
├── composer.json                            (✓ Exists - verify no changes needed)
├── phpstan.neon                             (✓ Exists - level max)
└── usage_example.php                        (📝 Update with new examples)
```

---

## 🔧 Implementation Phases

### Phase 1: Contracts & Interfaces

#### ✅ 1.1 Create FileValidator Interface
**File:** `src/Contracts/FileValidator.php`

```php
<?php
declare(strict_types=1);

namespace Maatify\Storage\Contracts;

use Psr\Http\Message\UploadedFileInterface;

/**
 * Contract for file validation.
 * 
 * Implementations must throw InvalidFileException on validation failure.
 */
interface FileValidator
{
    /**
     * Validate the uploaded file.
     * 
     * @param UploadedFileInterface $file The file to validate.
     * 
     * @throws InvalidFileException If validation fails.
     */
    public function validate(UploadedFileInterface $file): void;
}
```

---

### Phase 2: Configuration Classes

#### ✅ 2.1 Create ImageDimensions
**File:** `src/Config/ImageDimensions.php`

```php
<?php
declare(strict_types=1);

namespace Maatify\Storage\Config;

/**
 * Image dimension constraints.
 * 
 * Used by ImageDimensionsValidator to enforce min/max width/height.
 */
final readonly class ImageDimensions
{
    public function __construct(
        public int $minWidth,
        public int $minHeight,
        public int $maxWidth,
        public int $maxHeight,
    ) {}
}
```

---

### Phase 3: Exception Hierarchy

#### ✅ 3.1 Create StorageExceptionInterface
**File:** `src/Exception/StorageExceptionInterface.php`

Per MODULE_BUILDING_STANDARD section 6:

```php
<?php
declare(strict_types=1);

namespace Maatify\Storage\Exception;

/**
 * All Storage module exceptions implement this interface.
 */
interface StorageExceptionInterface extends \Throwable {}
```

---

#### ✅ 3.2 Update All Exceptions to Implement Interface + Named Constructors

**File:** `src/Exception/InvalidFileException.php`

Per MODULE_BUILDING_STANDARD section 6:
- Extend `\RuntimeException`
- Implement `StorageExceptionInterface`
- Use static named constructors (never `new InvalidFileException('...')`)
- No direct instantiation at call sites

```php
<?php
declare(strict_types=1);

namespace Maatify\Storage\Exception;

final class InvalidFileException extends \RuntimeException
    implements StorageExceptionInterface
{
    // Named constructors — required
    public static function unsupportedExtension(string $given, array $allowed): self
    {
        $allowedStr = implode(', ', $allowed);
        return new self("File extension [{$given}] not supported. Allowed: [{$allowedStr}].");
    }

    public static function fileTooLarge(int $given, int $maxSize): self
    {
        return new self(sprintf(
            "File size [%s] exceeds maximum allowed size [%s].",
            $this->formatBytes($given),
            $this->formatBytes($maxSize)
        ));
    }

    public static function imageTooSmall(int $width, int $height, ImageDimensions $dims): self
    {
        return new self(sprintf(
            "Image dimensions [%dx%d] below minimum [%dx%d].",
            $width,
            $height,
            $dims->minWidth,
            $dims->minHeight
        ));
    }

    public static function imageTooLarge(int $width, int $height, ImageDimensions $dims): self
    {
        return new self(sprintf(
            "Image dimensions [%dx%d] exceed maximum [%dx%d].",
            $width,
            $height,
            $dims->maxWidth,
            $dims->maxHeight
        ));
    }

    public static function invalidImage(): self
    {
        return new self("File is not a valid image.");
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
```

**Also update:**
- `AdapterException.php` → extend `\RuntimeException`, implement `StorageExceptionInterface`, use named constructors
- `ConfigurationException.php` → same as above
- `FileUploadException.php` → same as above

---

### Phase 4: Concrete Validators

#### ✅ 4.1 ExtensionValidator
**File:** `src/Validators/ExtensionValidator.php`

```php
<?php
declare(strict_types=1);

namespace Maatify\Storage\Validators;

use Maatify\Storage\Contracts\FileValidator;
use Maatify\Storage\Exceptions\InvalidFileException;
use Psr\Http\Message\UploadedFileInterface;

final class ExtensionValidator implements FileValidator
{
    /**
     * @param array<string> $allowedExtensions e.g. ['jpg', 'png', 'webp']
     */
    public function __construct(
        private readonly array $allowedExtensions,
    ) {}

    public function validate(UploadedFileInterface $file): void
    {
        $extension = strtolower(pathinfo(
            $file->getClientFilename() ?? '',
            PATHINFO_EXTENSION
        ));

        if (!in_array($extension, $this->allowedExtensions, true)) {
            throw InvalidFileException::unsupportedExtension(
                $extension,
                $this->allowedExtensions
            );
        }
    }
}
```

#### ✅ 4.2 SizeValidator
**File:** `src/Validators/SizeValidator.php`

```php
<?php
declare(strict_types=1);

namespace Maatify\Storage\Validators;

use Maatify\Storage\Contracts\FileValidator;
use Maatify\Storage\Exceptions\InvalidFileException;
use Psr\Http\Message\UploadedFileInterface;

final class SizeValidator implements FileValidator
{
    public function __construct(
        private readonly int $maxSizeBytes,
    ) {}

    public function validate(UploadedFileInterface $file): void
    {
        if ($file->getSize() > $this->maxSizeBytes) {
            throw InvalidFileException::fileTooLarge(
                $file->getSize(),
                $this->maxSizeBytes
            );
        }
    }
}
```

#### ✅ 4.3 ImageDimensionsValidator
**File:** `src/Validators/ImageDimensionsValidator.php`

```php
<?php
declare(strict_types=1);

namespace Maatify\Storage\Validators;

use Maatify\Storage\Config\ImageDimensions;
use Maatify\Storage\Contracts\FileValidator;
use Maatify\Storage\Exceptions\InvalidFileException;
use Psr\Http\Message\UploadedFileInterface;

final class ImageDimensionsValidator implements FileValidator
{
    public function __construct(
        private readonly ImageDimensions $dimensions,
    ) {}

    public function validate(UploadedFileInterface $file): void
    {
        $stream = $file->getStream();
        $stream->rewind();
        $imageInfo = @getimagesizefromstring($stream->getContents());

        if (!$imageInfo) {
            throw InvalidFileException::invalidImage();
        }

        [$width, $height] = $imageInfo;

        if ($width < $this->dimensions->minWidth ||
            $height < $this->dimensions->minHeight) {
            throw InvalidFileException::imageTooSmall(
                $width,
                $height,
                $this->dimensions
            );
        }

        if ($width > $this->dimensions->maxWidth ||
            $height > $this->dimensions->maxHeight) {
            throw InvalidFileException::imageTooLarge(
                $width,
                $height,
                $this->dimensions
            );
        }
    }
}
```

---

### Phase 5: Core Services

#### ✅ 5.1 Refactor FileUploadService
**File:** `src/Services/FileUploadService.php`

**Changes:**
- Remove hardcoded extensions and size limits
- Accept variadic `FileValidator ...$validators` parameter
- If 0 validators passed → no validation
- Generate filename internally

```php
<?php
declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\Contracts\FileValidator;
use Maatify\Storage\Contracts\StorageAdapterInterface;
use Psr\Http\Message\UploadedFileInterface;

final class FileUploadService
{
    public function __construct(
        private readonly StorageAdapterInterface $storage,
    ) {}

    /**
     * Upload a file with optional validators.
     *
     * @param UploadedFileInterface $file The uploaded file.
     * @param string $destinationPath The destination path (e.g. "products/slug-abc123.jpg").
     * @param FileValidator ...$validators Optional validators (if none, no validation).
     *
     * @return string The relative path to the stored file.
     *
     * @throws InvalidFileException If any validator fails.
     * @throws AdapterException If storage fails.
     */
    public function upload(
        UploadedFileInterface $file,
        string $destinationPath,
        FileValidator ...$validators
    ): string {
        // Apply all validators (if any)
        foreach ($validators as $validator) {
            $validator->validate($file);
        }

        // Store the file
        return $this->storage->store($file, $destinationPath);
    }
}
```

---

### Phase 6: Specialized Services

#### ✅ 6.1 Create ImageUploadService
**File:** `src/Services/ImageUploadService.php`

**Key Features:**
- Hardcoded defaults: extensions, max size
- Accept nullable parameters: extensions, size, dimensions
- Null = use defaults OR skip validation

```php
<?php
declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\Config\ImageDimensions;
use Maatify\Storage\Validators\ExtensionValidator;
use Maatify\Storage\Validators\ImageDimensionsValidator;
use Maatify\Storage\Validators\SizeValidator;
use Psr\Http\Message\UploadedFileInterface;

final class ImageUploadService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAX_SIZE = 5 * 1024 * 1024; // 5MB

    public function __construct(
        private readonly FileUploadService $uploadService,
    ) {}

    /**
     * Upload an image with optional custom constraints.
     *
     * @param UploadedFileInterface $file The uploaded image.
     * @param string $subfolder Destination subfolder (e.g. "products").
     * @param ?array<string> $allowedExtensions null = use defaults.
     * @param ?int $maxSizeBytes null = no size validation.
     * @param ?ImageDimensions $dimensions null = no dimension validation.
     *
     * @return string The public URL to the uploaded image.
     */
    public function upload(
        UploadedFileInterface $file,
        string $subfolder,
        ?array $allowedExtensions = null,
        ?int $maxSizeBytes = null,
        ?ImageDimensions $dimensions = null,
    ): string {
        $validators = [];

        // Extensions: null = use defaults
        $validators[] = new ExtensionValidator(
            $allowedExtensions ?? self::ALLOWED_EXTENSIONS
        );

        // Size: null = no validation
        if ($maxSizeBytes !== null) {
            $validators[] = new SizeValidator($maxSizeBytes);
        }

        // Dimensions: null = no validation
        if ($dimensions !== null) {
            $validators[] = new ImageDimensionsValidator($dimensions);
        }

        $filename = $this->generateFilename($file);
        $path = trim($subfolder, '/') . '/' . $filename;

        return $this->uploadService->upload($file, $path, ...$validators);
    }

    private function generateFilename(UploadedFileInterface $file): string
    {
        $originalName = $file->getClientFilename() ?? 'file';
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $sanitized = preg_replace('/[^a-z0-9-]/', '', strtolower($baseName));

        return sprintf(
            '%s-%s.%s',
            $sanitized ?: 'image',
            bin2hex(random_bytes(8)),
            strtolower($extension)
        );
    }
}
```

#### ✅ 6.2 Create VideoUploadService
**File:** `src/Services/VideoUploadService.php`

**Key Features:**
- Hardcoded defaults: extensions, max size (NO dimensions)
- Accept nullable parameters: extensions, size

```php
<?php
declare(strict_types=1);

namespace Maatify\Storage\Services;

use Maatify\Storage\Validators\ExtensionValidator;
use Maatify\Storage\Validators\SizeValidator;
use Psr\Http\Message\UploadedFileInterface;

final class VideoUploadService
{
    private const ALLOWED_EXTENSIONS = ['mp4', 'webm', 'mov', 'avi'];
    private const MAX_SIZE = 500 * 1024 * 1024; // 500MB

    public function __construct(
        private readonly FileUploadService $uploadService,
    ) {}

    /**
     * Upload a video with optional custom constraints.
     *
     * @param UploadedFileInterface $file The uploaded video.
     * @param string $subfolder Destination subfolder (e.g. "testimonials").
     * @param ?array<string> $allowedExtensions null = use defaults.
     * @param ?int $maxSizeBytes null = no size validation.
     *
     * @return string The public URL to the uploaded video.
     */
    public function upload(
        UploadedFileInterface $file,
        string $subfolder,
        ?array $allowedExtensions = null,
        ?int $maxSizeBytes = null,
    ): string {
        $validators = [];

        // Extensions: null = use defaults
        $validators[] = new ExtensionValidator(
            $allowedExtensions ?? self::ALLOWED_EXTENSIONS
        );

        // Size: null = no validation
        if ($maxSizeBytes !== null) {
            $validators[] = new SizeValidator($maxSizeBytes);
        }

        $filename = $this->generateFilename($file);
        $path = trim($subfolder, '/') . '/' . $filename;

        return $this->uploadService->upload($file, $path, ...$validators);
    }

    private function generateFilename(UploadedFileInterface $file): string
    {
        $originalName = $file->getClientFilename() ?? 'file';
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $sanitized = preg_replace('/[^a-z0-9-]/', '', strtolower($baseName));

        return sprintf(
            '%s-%s.%s',
            $sanitized ?: 'video',
            bin2hex(random_bytes(8)),
            strtolower($extension)
        );
    }
}
```

---

### Phase 7: Dependency Injection

#### ✅ 7.1 Update StorageBindings
**File:** `src/Bootstrap/StorageBindings.php`

Per MODULE_BUILDING_STANDARD section 16:
- Explicit `/** @var Type */` annotations (required)
- PSR-11 compatible
- Sections separated by comments

```php
<?php
declare(strict_types=1);

namespace Maatify\Storage\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Maatify\SharedCommon\Path\AppPaths;
use Maatify\Storage\Config\StorageConfig;
use Maatify\Storage\Contracts\StorageAdapterInterface;
use Maatify\Storage\Contracts\FileValidator;
use Maatify\Storage\Factory\StorageAdapterFactory;
use Maatify\Storage\Services\FileUploadService;
use Maatify\Storage\Services\ImageUploadService;
use Maatify\Storage\Services\VideoUploadService;
use Maatify\Storage\Validators\ExtensionValidator;
use Maatify\Storage\Validators\SizeValidator;
use Maatify\Storage\Validators\ImageDimensionsValidator;
use Psr\Container\ContainerInterface;

final class StorageBindings
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(
        ContainerBuilder $builder,
        string $rootPath,
        StorageConfig $config,
    ): void {
        $builder->addDefinitions([

            // ── Path ────────────────────────────────────────────────────

            AppPaths::class => static fn(): AppPaths
            => new AppPaths($rootPath),

            // ── Config ───────────────────────────────────────────────────

            StorageConfig::class => static fn(): StorageConfig
            => $config,

            // ── Adapter ──────────────────────────────────────────────────

            StorageAdapterInterface::class => static function (ContainerInterface $c): StorageAdapterInterface {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);

                /** @var StorageConfig $storageConfig */
                $storageConfig = $c->get(StorageConfig::class);

                return StorageAdapterFactory::create(
                    paths:  $paths,
                    config: $storageConfig,
                );
            },

            // ── Validators ───────────────────────────────────────────────

            ExtensionValidator::class => static function (): ExtensionValidator {
                // Validators are typically instantiated by services
                // This binding is optional, kept for explicit DI if needed
                return new ExtensionValidator([]);
            },

            SizeValidator::class => static function (): SizeValidator {
                return new SizeValidator(0);
            },

            ImageDimensionsValidator::class => static function (): ImageDimensionsValidator {
                // Instantiation deferred to services with actual config
                throw new \RuntimeException('ImageDimensionsValidator must be instantiated with dimensions.');
            },

            // ── Services ─────────────────────────────────────────────────

            FileUploadService::class => static function (ContainerInterface $c): FileUploadService {
                /** @var StorageAdapterInterface $adapter */
                $adapter = $c->get(StorageAdapterInterface::class);

                return new FileUploadService($adapter);
            },

            ImageUploadService::class => static function (ContainerInterface $c): ImageUploadService {
                /** @var FileUploadService $uploadService */
                $uploadService = $c->get(FileUploadService::class);

                return new ImageUploadService($uploadService);
            },

            VideoUploadService::class => static function (ContainerInterface $c): VideoUploadService {
                /** @var FileUploadService $uploadService */
                $uploadService = $c->get(FileUploadService::class);

                return new VideoUploadService($uploadService);
            },

        ]);
    }
}
```

---

### Phase 8: Update Project Files

#### ⏳ 8.1 ProductCommandController (athar-admin)
**File:** `Modules/ArPlatformSlim/src/Admin/Http/Controllers/Api/Products/ProductCommandController.php`

**Change from:**
```php
$this->fileUploadService->handleUpload($file, 'products', $slug);
```

**Change to:**
```php
$imageService = $container->get(ImageUploadService::class);
$publicUrl = $imageService->upload($file, 'products');
```

#### ⏳ 8.2 GalleryCommandController (athar-admin)
**File:** `Modules/ArPlatformSlim/src/Admin/Http/Controllers/Api/Gallery/GalleryCommandController.php`

**Change from:**
```php
$this->fileUploadService->handleUpload($file, 'gallery', $slug);
```

**Change to:**
```php
$imageService = $container->get(ImageUploadService::class);
$publicUrl = $imageService->upload($file, 'gallery');
```

---

## 📝 Usage Examples

### Simple Image Upload (All Defaults)
```php
$imageService = $container->get(ImageUploadService::class);
$url = $imageService->upload($file, 'products');
```

### Image with Custom Size Limit
```php
$url = $imageService->upload(
    $file,
    'products',
    null,  // use default extensions
    10 * 1024 * 1024  // 10MB
);
```

### Image with Custom Dimensions
```php
$url = $imageService->upload(
    $file,
    'profiles',
    ['jpg', 'png'],
    3 * 1024 * 1024,
    new ImageDimensions(50, 50, 500, 500)
);
```

### Simple Video Upload
```php
$videoService = $container->get(VideoUploadService::class);
$url = $videoService->upload($file, 'testimonials');
```

### Video with Custom Size
```php
$url = $videoService->upload(
    $file,
    'tutorials',
    ['mp4'],
    1024 * 1024 * 1024  // 1GB
);
```

---

## 🔍 PHPStan Compliance Checklist

Per MODULE_BUILDING_STANDARD section 19:

- [ ] All interfaces have proper `@var Type` in DI bindings
- [ ] All array parameters annotated: `@param array<string> $extensions`
- [ ] All return types explicit: `: string`, `: void`, `: bool`
- [ ] Validators check `UploadedFileInterface $file` without casting
- [ ] Services use type hints for dependencies (constructor injection)
- [ ] No direct file/directory operations without proper error handling
- [ ] All exceptions use named constructors (static factory methods)
- [ ] Stream rewinding: `$stream->rewind()` before `getimagesizefromstring()`

Example annotations needed:

```php
// ✅ Correct
/**
 * @param array<string> $allowedExtensions
 * @throws InvalidFileException
 */
public function __construct(array $allowedExtensions) {}

// ❌ Wrong
public function __construct(array $extensions) {}  // no @param annotation
```

---

## ✅ Validation Checklist

Before marking a phase complete:

- [ ] All files created/updated
- [ ] Zero PHPStan errors (level max)
- [ ] No hardcoded paths or magic strings
- [ ] All exceptions are named module exceptions
- [ ] Null handling works as expected
- [ ] Default constants are used correctly
- [ ] DI container wiring is correct
- [ ] Tests pass (if applicable)

---

## 🔄 Status Tracking

| Phase | Task | Status |
|-------|------|--------|
| 1 | FileValidator Interface | ⏳ Pending |
| 2 | ImageDimensions Config | ⏳ Pending |
| 3 | Update InvalidFileException | ⏳ Pending |
| 4.1 | ExtensionValidator | ⏳ Pending |
| 4.2 | SizeValidator | ⏳ Pending |
| 4.3 | ImageDimensionsValidator | ⏳ Pending |
| 5.1 | Refactor FileUploadService | ⏳ Pending |
| 6.1 | Create ImageUploadService | ⏳ Pending |
| 6.2 | Create VideoUploadService | ⏳ Pending |
| 7.1 | Update StorageBindings | ⏳ Pending |
| 8.1 | Update ProductCommandController | ⏳ Pending |
| 8.2 | Update GalleryCommandController | ⏳ Pending |

---

**Ready to start implementation! 🚀**
