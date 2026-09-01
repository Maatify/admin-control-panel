# Installation Guide

## Prerequisites

- **PHP 8.4+**: Modern PHP version with type hints and readonly properties
- **Composer**: For dependency management
- **Git**: For version control (optional)

## Installation Methods

### 1. As Maatify Project Module (Current)

The Storage Module is already integrated into the Maatify project:

```bash
# Already included in Modules/Storage
# Just ensure composer dependencies are installed
composer install
```

### 2. As Standalone Library (Via Composer)

Once extracted to its own repository:

```bash
composer require maatify/storage
```

### 3. Manual Installation

For development or custom setup:

```bash
# Clone the repository
git clone https://github.com/maatify/storage.git
cd storage

# Install dependencies
composer install

# Verify installation (run tests)
composer run-script test
```

---

## Configuration

### Environment Variables

Create a `.env` file in your project root:

```env
# Storage driver
STORAGE_DRIVER=local

# Local storage (for development)
LOCAL_BASE_PATH=/var/www/storage
LOCAL_BASE_URL=/uploads

# DigitalOcean Spaces (for production)
DO_SPACES_KEY=your-api-key
DO_SPACES_SECRET=your-api-secret
DO_SPACES_ENDPOINT=https://nyc3.digitaloceanspaces.com
DO_SPACES_BUCKET=my-bucket
DO_SPACES_REGION=nyc3
DO_SPACES_CDN_URL=https://my-bucket.nyc3.cdn.digitaloceanspaces.com
DO_SPACES_ACL=public-read
```

### Programmatic Configuration

In your application bootstrap:

```php
use Maatify\Storage\Config\StorageConfig;
use DI\ContainerBuilder;
use Maatify\Storage\Bootstrap\StorageBindings;

$containerBuilder = new ContainerBuilder();

// Load configuration from environment
$config = StorageConfig::fromEnv($_ENV);

// Register all Storage bindings in the DI container
StorageBindings::register($containerBuilder, APP_ROOT, $config);

// Build container
$container = $containerBuilder->build();

// Services are now available for dependency injection
$imageService = $container->get(ImageUploadService::class);
$videoService = $container->get(VideoUploadService::class);
```

---

## Directory Setup

### Create Required Directories

```bash
# Create storage directory with proper permissions
mkdir -p storage/{uploads,products,videos,documents,temp}
chmod -R 755 storage/
chmod -R 777 storage/temp  # Writable for uploads

# Create public symlink (for web access)
ln -s ../storage/uploads public/uploads
```

### Directory Structure

```
project-root/
├── storage/
│   ├── uploads/          # Main upload directory
│   ├── products/         # Product images
│   ├── videos/           # Video files
│   ├── documents/        # Document files
│   └── temp/             # Temporary files
├── public/
│   └── uploads -> ../storage/uploads  # Symlink for web access
└── .env
```

---

## Integration with Dependency Injection

### Using PHP-DI (Recommended)

```php
use Maatify\Storage\Services\AudioUploadService;
use Maatify\Storage\Services\ImageUploadService;
use Maatify\Storage\Services\VideoUploadService;

class ProductController {
    public function __construct(
        private AudioUploadService $audioService,
        private ImageUploadService $imageService,
        private VideoUploadService $videoService
    ) {}

    public function upload(ServerRequestInterface $request): ResponseInterface {
        $file = $request->getUploadedFiles()['image'];
        $storedFile = $this->imageService->upload($file, 'products');
        // Use $storedFile->url for display, $storedFile->path for storage
        // ... or use audio/video services similarly
    }
}
```

### Manual Registration

```php
// In your DI container configuration
$container->set(ImageUploadService::class, function() {
    $storageAdapter = new LocalStorageAdapter(...);
    $fileUploadService = new FileUploadService($storageAdapter);
    return new ImageUploadService($fileUploadService);
});
```

---

## Verification

### Test Installation

```bash
# Run test suite
composer run-script test

# Run code analysis
composer run-script phpstan

# Run both
composer run-script analyze
```

### Quick Test: Single Storage with Base Services

```php
<?php

require_once 'vendor/autoload.php';

use Maatify\Storage\Config\StorageConfig;
use Maatify\Storage\Services\AudioUploadService;
use Maatify\Storage\Services\ImageUploadService;
use Maatify\Storage\Services\VideoUploadService;
use Maatify\Storage\Services\AudioReaderService;
use Maatify\Storage\Services\ImageReaderService;
use Maatify\Storage\Services\VideoReaderService;
use Maatify\Storage\Services\FileServeService;
use Maatify\Storage\Bootstrap\StorageBindings;
use DI\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
$projectRoot = getcwd();

// Load configuration from environment
$config = StorageConfig::fromEnv($_ENV);

// Register single-storage setup
StorageBindings::register($containerBuilder, $projectRoot, $config);
$container = $containerBuilder->build();

// Upload services
$imageUpload = $container->get(ImageUploadService::class);
$videoUpload = $container->get(VideoUploadService::class);
$audioUpload = $container->get(AudioUploadService::class);

// Reader services
$imageReader = $container->get(ImageReaderService::class);
$videoReader = $container->get(VideoReaderService::class);
$audioReader = $container->get(AudioReaderService::class);

// Serve service (local driver only — streams private files outside web root)
$fileServe = $container->get(FileServeService::class);

echo "✅ Storage Module initialized successfully!\n";
echo "Upload Services: ImageUploadService, VideoUploadService, AudioUploadService\n";
echo "Reader Services: ImageReaderService, VideoReaderService, AudioReaderService\n";
echo "Serve Service:   FileServeService (local private files with Range support)\n";
?>
```

Run with:
```bash
php test_installation.php
```

**For multi-disk setup with 6 public/private wrapper services, see [MULTI_DISK_GUIDE.md](./MULTI_DISK_GUIDE.md).**

---

## Troubleshooting

### "Missing required environment variable"

**Problem:** ConfigurationException: "Missing required environment variable: DO_SPACES_KEY"

**Solution:** Check your `.env` file has all required DO_SPACES variables, or set `STORAGE_DRIVER=local`

### "Could not read uploaded file stream"

**Problem:** FileUploadException: "Could not read uploaded file stream"

**Solution:** Ensure the uploaded file is readable and not corrupted

### "File size exceeds maximum"

**Problem:** InvalidFileException: "File size [X] exceeds maximum allowed size [Y]"

**Solution:** Either:
- Increase the max size parameter:
  ```php
  $imageService->upload($file, 'images', maxSizeBytes: 20 * 1024 * 1024)
  ```
- Or increase PHP's upload limits in `php.ini`:
  ```ini
  upload_max_filesize = 100M
  post_max_size = 100M
  ```

### "Extension not allowed"

**Problem:** InvalidFileException: 'Invalid file extension "gif". Allowed: jpg, jpeg, png, webp.'

**Solution:** Either allow the extension:
  ```php
  $imageService->upload($file, 'images', allowedExtensions: ['jpg', 'png', 'gif'])
  ```
- Or upload a file with an allowed extension

### Tests fail with "Class not found"

**Problem:** Tests fail with autoload errors

**Solution:** Ensure bootstrap.php path is correct:
```bash
# Must be run from project root
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php \
    Modules/Storage/tests/Unit
```

---

## Performance Tuning

### PHP Configuration

For production deployments, optimize your `php.ini`:

```ini
; Allow larger uploads
upload_max_filesize = 100M
post_max_size = 100M

; Memory for large file processing
memory_limit = 512M

; Timeout for large uploads
max_execution_time = 300
```

### DigitalOcean Spaces Configuration

For production with DO Spaces:

```env
# Use edge location nearest to your users
DO_SPACES_ENDPOINT=https://nyc3.digitaloceanspaces.com  # New York
DO_SPACES_ENDPOINT=https://sfo3.digitaloceanspaces.com  # San Francisco
DO_SPACES_ENDPOINT=https://ams3.digitaloceanspaces.com  # Amsterdam
DO_SPACES_ENDPOINT=https://sgp1.digitaloceanspaces.com  # Singapore

# Use CDN URL for cached delivery
DO_SPACES_CDN_URL=https://my-bucket.nyc3.cdn.digitaloceanspaces.com
```

---

## Advanced: Multi-Configuration Setup (5+ Buckets)

For projects requiring multiple storage backends (public, private, media, backups, cdn):

### Quick Overview

```php
// Create configuration classes for each scenario
class PublicStorageConfig implements StorageConfigInterface { ... }
class PrivateStorageConfig implements StorageConfigInterface { ... }
class MediaStorageConfig implements StorageConfigInterface { ... }

// Create StorageManager to manage them, then use with services
$adapter = $storageManager->disk('public');
$imageService = new ImageUploadService(new FileUploadService($adapter));
$storedFile = $imageService->upload($file, 'images', customBaseName: 'product-slug');
echo $storedFile->url;  // Use for display

$adapter = $storageManager->disk('private');
$fileService = new FileUploadService($adapter);
// FileUploadService builds path directly (no customBaseName parameter)
$semanticPath = 'images/img-001-' . bin2hex(random_bytes(8)) . '.jpg';
$storedFile = $fileService->upload($file, $semanticPath);
// Use $storedFile->path to generate signed URLs

$adapter = $storageManager->disk('media');
$videoService = new VideoUploadService(new FileUploadService($adapter));
$storedFile = $videoService->upload($file, 'videos', customBaseName: 'tutorial-5');
echo $storedFile->url;  // Use for display
```

### Complete Guide

**See [EXTENSION_GUIDE.md](./EXTENSION_GUIDE.md) for:**
- Step-by-step implementation
- Complete code examples
- Configuration for 5 different bucket types
- DI container registration
- Controller usage patterns

---

## Next Steps

After installation:

1. **Read the [README.md](./README.md)** for usage examples
2. **Check [TESTING.md](./TESTING.md)** to run tests
3. **Review [usage_example.php](./usage_example.php)** for code patterns
4. **For Advanced**: See [EXTENSION_GUIDE.md](./EXTENSION_GUIDE.md) for multi-configuration setups
5. **Explore [src/](./src/)** to understand the architecture

---

## Support

- 📖 [README.md](./README.md) - Full feature documentation
- 🧪 [TESTING.md](./TESTING.md) - Testing guide
- 📋 [CHANGELOG.md](./CHANGELOG.md) - Version history
- ✅ [LIBRARY_CHECKLIST.md](./LIBRARY_CHECKLIST.md) - Feature checklist

---

**Installation complete! You're ready to use the Storage Module.** 🚀
