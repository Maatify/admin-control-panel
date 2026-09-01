# Storage Module Extension Guide

## Overview

The Storage Module is designed to be **simple by default** and **extensible by design**.

- **Default case**: Use `StorageConfig::fromEnv()` - read from `.env`
- **Advanced case**: Implement `StorageConfigInterface` for custom configurations

## Use Cases

### Case 1: Simple Projects (Single Storage)

Just use the default configuration:

```php
// In your bootstrap
$config = StorageConfig::fromEnv($_ENV);
StorageBindings::register($container, APP_ROOT, $config);

// That's it! Use storage normally
```

No additional code needed. Works with simple `.env` configuration.

---

### Case 2: Complex Projects (Multiple Storages)

Create custom configuration classes for each storage scenario.

## Implementing Custom Configurations

### Step 1: Implement `StorageConfigInterface`

```php
// app/Config/Storage/PublicStorageConfig.php

namespace App\Config\Storage;

use Maatify\Storage\Config\DOSpacesConfig;
use Maatify\Storage\Config\LocalStorageConfig;
use Maatify\Storage\Contracts\StorageConfigInterface;

final readonly class PublicStorageConfig implements StorageConfigInterface
{
    public function getDriver(): string
    {
        return 'do_spaces';
    }

    public function getLocalConfig(): ?LocalStorageConfig
    {
        return null; // Not using local storage
    }

    public function getDoSpacesConfig(): ?DOSpacesConfig
    {
        return new DOSpacesConfig(
            key: $_ENV['PUBLIC_BUCKET_KEY'] ?? '',
            secret: $_ENV['PUBLIC_BUCKET_SECRET'] ?? '',
            endpoint: $_ENV['DO_SPACES_ENDPOINT'] ?? '',
            bucket: $_ENV['PUBLIC_BUCKET'] ?? 'public-bucket',
            region: $_ENV['DO_SPACES_REGION'] ?? 'nyc3',
            cdnUrl: $_ENV['PUBLIC_BUCKET_CDN'] ?? '',
            acl: 'public-read',
        );
    }
}
```

### Step 2: Create More Configurations

```php
// app/Config/Storage/PrivateStorageConfig.php

namespace App\Config\Storage;

use Maatify\Storage\Config\DOSpacesConfig;
use Maatify\Storage\Config\LocalStorageConfig;
use Maatify\Storage\Contracts\StorageConfigInterface;

final readonly class PrivateStorageConfig implements StorageConfigInterface
{
    public function getDriver(): string
    {
        return 'do_spaces';
    }

    public function getLocalConfig(): ?LocalStorageConfig
    {
        return null;
    }

    public function getDoSpacesConfig(): ?DOSpacesConfig
    {
        return new DOSpacesConfig(
            key: $_ENV['PRIVATE_BUCKET_KEY'] ?? '',
            secret: $_ENV['PRIVATE_BUCKET_SECRET'] ?? '',
            endpoint: $_ENV['DO_SPACES_ENDPOINT'] ?? '',
            bucket: $_ENV['PRIVATE_BUCKET'] ?? 'private-bucket',
            region: $_ENV['DO_SPACES_REGION'] ?? 'nyc3',
            cdnUrl: $_ENV['PRIVATE_BUCKET_CDN'] ?? '',
            acl: 'private',
        );
    }
}
```

### Step 3: Create a Storage Manager

```php
// app/Services/StorageManager.php

namespace App\Services;

use Maatify\Storage\Contracts\StorageConfigInterface;
use Maatify\Storage\Factory\StorageAdapterFactory;
use Maatify\Storage\Contracts\StorageAdapterInterface;
use Maatify\SharedCommon\Path\AppPaths;

final class StorageManager
{
    private array $adapters = [];

    public function __construct(
        private AppPaths $paths,
        private array $configs, // ['public' => PublicStorageConfig, 'private' => PrivateStorageConfig]
    ) {}

    public function disk(string $name): StorageAdapterInterface
    {
        if (!isset($this->adapters[$name])) {
            $config = $this->configs[$name]
                ?? throw new \RuntimeException("Storage disk '{$name}' not configured");

            $this->adapters[$name] = StorageAdapterFactory::create($this->paths, $config);
        }

        return $this->adapters[$name];
    }
}
```

### Step 4: Register in DI Container

```php
// app/Bootstrap/ServiceProviders.php

namespace App\Bootstrap;

use App\Config\Storage\PublicStorageConfig;
use App\Config\Storage\PrivateStorageConfig;
use App\Config\Storage\MediaStorageConfig;
use App\Config\Storage\BackupStorageConfig;
use App\Config\Storage\CdnStorageConfig;
use App\Services\StorageManager;
use Maatify\SharedCommon\Path\AppPaths;
use DI\Container;

class StorageServiceProvider
{
    public static function register(Container $container, AppPaths $paths): void
    {
        // Create individual configs
        $configs = [
            'public' => new PublicStorageConfig(),
            'private' => new PrivateStorageConfig(),
            'media' => new MediaStorageConfig(),
            'backup' => new BackupStorageConfig(),
            'cdn' => new CdnStorageConfig(),
        ];

        // Register StorageManager
        $container->set(StorageManager::class, function() use ($paths, $configs) {
            return new StorageManager($paths, $configs);
        });
    }
}
```

### Step 5: Use Readers in Controllers

For reading files, use `ReaderAdapterFactory` the same way:

```php
// app/Http/Controllers/ProductController.php

namespace App\Http\Controllers;

use App\Services\StorageManager;
use Maatify\Storage\Services\ImageReaderService;
use Maatify\Storage\Services\FileReaderService;
use Maatify\Storage\Factory\ReaderAdapterFactory;

final readonly class ProductController
{
    public function __construct(
        private StorageManager $storageManager,
        private AppPaths $paths,
    ) {}

    public function getProductImage(string $productSlug)
    {
        // Get the reader adapter for public storage
        $adapter = ReaderAdapterFactory::create(
            $this->paths,
            new PublicStorageConfig()
        );
        
        // Wrap in reader service for type-specific operations
        $imageReader = new ImageReaderService(
            new FileReaderService($adapter)
        );
        
        // Get access URL
        $imagePath = "products/{$productSlug}-abc123def456.jpg";
        $accessResult = $imageReader->getAccessUrl($imagePath);
        
        // Get metadata
        $metadata = $imageReader->getMetadata($imagePath);
        
        return [
            'url' => $accessResult->url,
            'size' => $metadata->sizeBytes,
            'type' => $metadata->contentType
        ];
    }
}
```

### Step 6: Use Upload & Read Together in Controllers

**Important**: StorageAdapterInterface has `store()` method. To get `upload()` with validation and semantic naming, wrap the adapter in the appropriate service:

```php
// app/Http/Controllers/ProductController.php

namespace App\Http\Controllers;

use App\Services\StorageManager;
use Maatify\Storage\Services\ImageUploadService;
use Maatify\Storage\Services\FileUploadService;
use Psr\Http\Message\UploadedFileInterface;

final readonly class ProductController
{
    public function __construct(
        private StorageManager $storageManager,
    ) {}

    public function uploadImage(UploadedFileInterface $file, string $productSlug)
    {
        // Get the adapter for public storage
        $adapter = $this->storageManager->disk('public');
        
        // Wrap in services to get upload() with validation and semantic naming
        $imageService = new ImageUploadService(
            new FileUploadService($adapter)
        );
        
        // Upload with semantic naming
        $storedFile = $imageService->upload(
            $file,
            'images/products',
            customBaseName: $productSlug
        );
        
        return ['url' => $storedFile->url];
    }
}
```

```php
// app/Http/Controllers/DocumentController.php

namespace App\Http\Controllers;

use App\Services\StorageManager;
use Maatify\Storage\Services\FileUploadService;
use Psr\Http\Message\UploadedFileInterface;

final readonly class DocumentController
{
    public function __construct(
        private StorageManager $storageManager,
    ) {}

    public function uploadDocument(UploadedFileInterface $file, string $userId)
    {
        // Get the adapter for private storage
        $adapter = $this->storageManager->disk('private');
        
        // Wrap in service for validation
        // Note: FileUploadService doesn't have semantic naming, so we build the path directly
        $fileService = new FileUploadService($adapter);
        
        // Upload with semantic naming in path
        $semanticPath = "documents/user/user-{$userId}-" . bin2hex(random_bytes(8)) . '.jpg';
        $storedFile = $fileService->upload($file, $semanticPath);
        
        // For private uploads, generate a signed URL from the storage path
        // Example: $signedUrl = $this->generateSignedUrl($storedFile->path);
        return ['path' => $storedFile->path];
    }
}
```

**Alternative**: If you prefer direct adapter usage without validation wrapper:

```php
// Direct adapter store() - minimal validation
$adapter = $this->storageManager->disk('public');
$storedFile = $adapter->store($file, 'images/products/filename.jpg');

// Returns StoredFile DTO with path and url properties
echo $storedFile->url;   // Full CDN URL
echo $storedFile->path;  // Storage path (without leading slash)
```

---

## Complete Upload + Read Lifecycle Example

```php
// app/Http/Controllers/MediaController.php

namespace App\Http\Controllers;

use App\Services\StorageManager;
use Maatify\Storage\Services\ImageUploadService;
use Maatify\Storage\Services\ImageReaderService;
use Maatify\Storage\Services\FileUploadService;
use Maatify\Storage\Services\FileReaderService;
use Maatify\Storage\Factory\StorageAdapterFactory;
use Maatify\Storage\Factory\ReaderAdapterFactory;
use App\Config\Storage\PublicStorageConfig;
use Maatify\SharedCommon\Path\AppPaths;

final readonly class MediaController
{
    public function __construct(
        private AppPaths $paths,
    ) {}

    // ──────── UPLOAD SIDE ────────
    
    public function uploadProductImage($file, $productSlug)
    {
        // Create write adapter
        $writeAdapter = StorageAdapterFactory::create(
            $this->paths,
            new PublicStorageConfig()
        );
        
        // Wrap in services for validation + semantic naming
        $imageUploadService = new ImageUploadService(
            new FileUploadService($writeAdapter)
        );
        
        // Upload file
        $storedFile = $imageUploadService->upload(
            $file,
            'products',
            customBaseName: $productSlug
        );
        
        // Store path in database
        $this->db->update('products', [
            'image_path' => $storedFile->path,
        ]);
        
        return [
            'success' => true,
            'image_url' => $storedFile->url,  // CDN URL for immediate display
            'storage_path' => $storedFile->path
        ];
    }

    // ──────── READ SIDE ────────
    
    public function getProductImage($storagePath)
    {
        // Create read adapter
        $readAdapter = ReaderAdapterFactory::create(
            $this->paths,
            new PublicStorageConfig()
        );
        
        // Wrap in services for type-specific operations
        $imageReaderService = new ImageReaderService(
            new FileReaderService($readAdapter)
        );
        
        // Check if file exists
        if (!$imageReaderService->exists($storagePath)) {
            throw new \Exception('Image not found');
        }
        
        // Get access URL and metadata
        $accessResult = $imageReaderService->getAccessUrl($storagePath);
        $metadata = $imageReaderService->getMetadata($storagePath);
        
        return [
            'url' => $accessResult->url,        // Adapter-dependent: presigned S3 URL (DO Spaces) or relative path (Local)
            'is_signed' => $accessResult->isSigned,
            'size_bytes' => $metadata->sizeBytes,
            'content_type' => $metadata->contentType,
            'last_modified' => $metadata->lastModified
        ];
    }

    // ──────── PRIVATE STORAGE (Upload + Reader Access) ────────
    
    public function uploadPrivateDocument($file, $userId)
    {
        // Create write adapter for private bucket
        $writeAdapter = StorageAdapterFactory::create(
            $this->paths,
            new PrivateStorageConfig()  // Different config
        );
        
        // Upload file
        $fileUploadService = new FileUploadService($writeAdapter);
        $semanticPath = "user-{$userId}/documents/doc-" . bin2hex(random_bytes(8)) . '.pdf';
        $storedFile = $fileUploadService->upload($file, $semanticPath);
        
        // Store path in database for later retrieval
        $this->db->update('documents', [
            'storage_path' => $storedFile->path,
        ]);
        
        return [
            'success' => true,
            'storage_path' => $storedFile->path
        ];
    }
    
    public function downloadPrivateDocument($storagePath, $userId)
    {
        // Verify access
        if (!$this->userCanAccess($storagePath, $userId)) {
            throw new \Exception('Unauthorized');
        }
        
        // Create read adapter for private bucket
        $readAdapter = ReaderAdapterFactory::create(
            $this->paths,
            new PrivateStorageConfig()
        );
        
        $fileReaderService = new FileReaderService($readAdapter);
        
        // Get access URL from reader — adapter resolves it internally
        // DO Spaces: returns a presigned S3 GetObject URL (isSigned=true)
        // Local:     returns a relative application-controlled path (isSigned=false)
        $accessResult = $fileReaderService->getAccessUrl($storagePath);
        $metadata = $fileReaderService->getMetadata($storagePath);
        
        return [
            'download_url' => $accessResult->url,        // Use directly
            'is_signed' => $accessResult->isSigned,      // true = already presigned
            'size_bytes' => $metadata->sizeBytes,
            'expires_in_seconds' => $accessResult->expirationSeconds
        ];
    }
}
```

**Key Points:**
- ✅ **Symmetric API**: Upload and read operations mirror each other
- ✅ **Same Configuration**: Both use `StorageConfigInterface` implementations
- ✅ **Same Factories**: `StorageAdapterFactory` and `ReaderAdapterFactory` follow same pattern
- ✅ **Type Safety**: Full interface contracts for IDE support
- ✅ **Separation**: Write adapter for upload, read adapter for retrieval
- ✅ **Private Access**: Store paths in the database and use Reader services to resolve adapter-specific access URLs. DO Spaces readers return presigned GetObject URLs; Local readers return application-controlled relative paths.

---

## Environment Variables

For the custom configuration example above, add to `.env`:

```env
# Public bucket
PUBLIC_BUCKET_KEY=your-public-key
PUBLIC_BUCKET_SECRET=your-public-secret
PUBLIC_BUCKET=public-bucket
PUBLIC_BUCKET_CDN=https://public.cdn.example.com

# Private bucket
PRIVATE_BUCKET_KEY=your-private-key
PRIVATE_BUCKET_SECRET=your-private-secret
PRIVATE_BUCKET=private-bucket
PRIVATE_BUCKET_CDN=https://private.cdn.example.com

# Media bucket
MEDIA_BUCKET_KEY=your-media-key
MEDIA_BUCKET_SECRET=your-media-secret
MEDIA_BUCKET=media-bucket
MEDIA_BUCKET_CDN=https://media.cdn.example.com

# Backup bucket
BACKUP_BUCKET_KEY=your-backup-key
BACKUP_BUCKET_SECRET=your-backup-secret
BACKUP_BUCKET=backup-bucket

# CDN bucket
CDN_BUCKET_KEY=your-cdn-key
CDN_BUCKET_SECRET=your-cdn-secret
CDN_BUCKET=cdn-bucket
CDN_BUCKET_URL=https://cdn.cdn.example.com

# Shared
DO_SPACES_ENDPOINT=https://nyc3.digitaloceanspaces.com
DO_SPACES_REGION=nyc3
```

---

## Architecture Diagram

### Simple Project

```
.env
  ↓
StorageConfig::fromEnv()
  ↓
StorageAdapterFactory::create()
  ↓
ImageUploadService / VideoUploadService
```

### Complex Project

```
.env
  ↓
PublicStorageConfig ────┐
PrivateStorageConfig ──┤─→ StorageManager
MediaStorageConfig ────┤
BackupStorageConfig ───┤
CdnStorageConfig ──────┘
  ↓
StorageAdapterFactory::create()
  ↓
ImageUploadService / VideoUploadService
```

---

## Key Points

✅ **Backward Compatible**: Default `StorageConfig` still works as before
✅ **Extensible**: Implement `StorageConfigInterface` for custom needs
✅ **Type Safe**: Full interface contracts for IDE support
✅ **No Breaking Changes**: Existing code continues to work
✅ **Production Ready**: Same module works for simple and complex projects

---

## Migration Path

### From Simple to Complex

If your project grows and needs multiple storages:

1. Create new config classes (PublicStorageConfig, PrivateStorageConfig, etc.)
2. Create StorageManager (or similar service)
3. Update controllers to use `$storageManager->disk('name')`
4. Add environment variables for each bucket

**Zero module changes needed!** The module already supports everything.

---

## Summary

The Storage Module is:

- **Simple**: Default case requires no custom code
- **Extensible**: Implement `StorageConfigInterface` for advanced cases
- **Flexible**: Mix local and remote storage
- **Type-Safe**: Full interface contracts
- **Production-Ready**: Battle-tested architecture

Choose the complexity level that matches your needs! 🚀
