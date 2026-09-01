# Multi-Disk Storage Integration Checklist

## Project Integration Steps

This checklist guides you through integrating the 6-service multi-disk storage architecture into your Athar admin application.

### Phase 1: Environment Setup ✓

- [ ] Copy environment variables from `.env.example` to `.env`
- [ ] Fill in all 12 DigitalOcean Spaces credentials:
  ```bash
  PUBLIC_IMAGES_KEY, PUBLIC_IMAGES_SECRET, PUBLIC_IMAGES_BUCKET, PUBLIC_IMAGES_REGION, PUBLIC_IMAGES_CDN
  PRIVATE_IMAGES_KEY, PRIVATE_IMAGES_SECRET, PRIVATE_IMAGES_BUCKET, PRIVATE_IMAGES_REGION
  PUBLIC_VIDEOS_KEY, PUBLIC_VIDEOS_SECRET, PUBLIC_VIDEOS_BUCKET, PUBLIC_VIDEOS_REGION, PUBLIC_VIDEOS_CDN
  PRIVATE_VIDEOS_KEY, PRIVATE_VIDEOS_SECRET, PRIVATE_VIDEOS_BUCKET, PRIVATE_VIDEOS_REGION
  PUBLIC_AUDIO_KEY, PUBLIC_AUDIO_SECRET, PUBLIC_AUDIO_BUCKET, PUBLIC_AUDIO_REGION, PUBLIC_AUDIO_CDN
  PRIVATE_AUDIO_KEY, PRIVATE_AUDIO_SECRET, PRIVATE_AUDIO_BUCKET, PRIVATE_AUDIO_REGION
  ```
- [ ] Create 6 DigitalOcean Spaces buckets with names matching your `.env`
- [ ] Enable CDN on public buckets (images, videos, audio)
- [ ] Verify API keys have Spaces access permissions

### Phase 2: Service Registration ✓

The following are already implemented:

- [x] **StorageConfigs.php** - Factory class for 6 configurations (used for both upload and reader)
- [x] **MediaServiceProvider.php** - DI registration for all 12 services (6 upload + 6 reader)
- [x] **Service Aliases** - Created 6 typed service classes:
  - `ImageUploadPublicService`
  - `ImageUploadPrivateService`
  - `VideoUploadPublicService`
  - `VideoUploadPrivateService`
  - `AudioUploadPublicService`
  - `AudioUploadPrivateService`
- [x] **StorageBindings** - Updated with `registerMultiDisk()` method
- [x] **.env.example** - Updated with all 12 variables + documentation

### Phase 3: Application Bootstrap

Choose ONE approach based on your application's bootstrap structure:

#### Option A: Using PHP-DI ContainerBuilder (Recommended)

In your `bootstrap.php` or kernel initialization:

```php
<?php
use DI\ContainerBuilder;
use Maatify\Storage\Bootstrap\StorageBindings;

$containerBuilder = new ContainerBuilder();

// Register multi-disk storage architecture
StorageBindings::registerMultiDisk($containerBuilder, $projectRoot);

$container = $containerBuilder->build();
```

#### Option B: Using Existing SingleTon Container

If your application already has a container instance:

```php
<?php
use DI\ContainerBuilder;
use Maatify\Storage\Bootstrap\MediaServiceProvider;

$builder = new ContainerBuilder();
MediaServiceProvider::register($builder, $projectRoot);

// Then build and use the container
$container = $builder->build();
```

#### Option C: Post-Container-Build Registration (If Available)

If you prefer to register services after container building:

```php
<?php
use Maatify\Storage\Bootstrap\MediaServiceProvider;

// After container is built
$builder = new ContainerBuilder();
MediaServiceProvider::register($builder, $projectRoot);
```

### Phase 4: Create Reader Controllers (Optional)

If your application needs to retrieve or verify stored files:

#### Example: ProductController (Read Public Images)

```php
<?php
namespace App\Http\Controllers;

use Maatify\Storage\Services\ImageReaderPublicService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ProductController {
    public function __construct(
        private ImageReaderPublicService $imageReader
    ) {}

    public function getProductImage(
        ServerRequestInterface $request
    ): ResponseInterface {
        $productId = $request->getAttribute('product_id');
        $imagePath = "products/product-{$productId}-abc123def456.jpg";

        try {
            // Check if image exists
            if (!$this->imageReader->exists($imagePath)) {
                return $this->json(['error' => 'Image not found'], 404);
            }

            // Get access URL — adapter-dependent format
            // DO Spaces: presigned S3 GetObject URL (isSigned=true)
            // Local:     relative application-controlled path (isSigned=false)
            $accessResult = $this->imageReader->getAccessUrl($imagePath);
            
            // Get metadata for auditing
            $metadata = $this->imageReader->getMetadata($imagePath);

            return $this->json([
                'success' => true,
                'image_url' => $accessResult->url,
                'size_bytes' => $metadata->sizeBytes,
                'content_type' => $metadata->contentType
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    private function json(array $data, int $statusCode = 200): ResponseInterface {
        // Implement based on your framework
    }
}
```

#### Example: PrivateImageController (Read Private Images)

```php
<?php
namespace App\Http\Controllers;

use Maatify\Storage\Services\ImageReaderPrivateService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class PrivateImageController {
    public function __construct(
        private ImageReaderPrivateService $imageReader
    ) {}

    public function downloadDocument(
        ServerRequestInterface $request
    ): ResponseInterface {
        $userId = $request->getAttribute('user_id');
        $documentPath = "user-{$userId}/documents/document-abc123def456.jpg";

        try {
            // Check document exists
            if (!$this->imageReader->exists($documentPath)) {
                return $this->json(['error' => 'Document not found'], 404);
            }

            // Get access URL from reader — adapter resolves it internally
            // DO Spaces: returns presigned S3 GetObject URL (isSigned=true)
            // Local:     returns relative application-controlled path (isSigned=false)
            $accessResult = $this->imageReader->getAccessUrl($documentPath);
            $metadata = $this->imageReader->getMetadata($documentPath);

            return $this->json([
                'success' => true,
                'download_url' => $accessResult->url,              // Use directly
                'is_signed' => $accessResult->isSigned,            // true = already presigned
                'size_bytes' => $metadata->sizeBytes,
                'expires_in_seconds' => $accessResult->expirationSeconds
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

### Phase 5: Create Upload Controllers

Implement controllers that inject the services they need:

#### Example: ProductController (Public Images)

```php
<?php
namespace App\Http\Controllers;

use Maatify\Storage\Services\ImageUploadPublicService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ProductController {
    public function __construct(
        private ImageUploadPublicService $imageService
    ) {}

    public function uploadProductImage(
        ServerRequestInterface $request
    ): ResponseInterface {
        $file = $request->getUploadedFiles()['product_image'];
        $productId = $request->getAttribute('product_id');

        try {
            $storedFile = $this->imageService->upload(
                $file,
                'products',
                allowedExtensions: ['jpg', 'jpeg', 'png', 'webp'],
                maxSizeBytes: 5 * 1024 * 1024,  // 5MB
                customBaseName: "product-{$productId}"
            );

            return $this->json([
                'success' => true,
                'image_url' => $storedFile->url,
                'message' => 'Image uploaded successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    private function json(array $data, int $statusCode = 200): ResponseInterface {
        // Implement based on your framework
    }
}
```

#### Example: DocumentController (Private Images)

```php
<?php
namespace App\Http\Controllers;

use Maatify\Storage\Services\ImageUploadPrivateService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class DocumentController {
    public function __construct(
        private ImageUploadPrivateService $imageService,
        private ImageReaderPrivateService $imageReader
    ) {}

    public function uploadUserDocument(
        ServerRequestInterface $request
    ): ResponseInterface {
        $file = $request->getUploadedFiles()['document'];
        $userId = $request->getAttribute('user_id');
        $type = $request->getAttribute('document_type');

        try {
            // Upload to private bucket
            $storedFile = $this->imageService->upload(
                $file,
                "user-{$userId}/images",
                allowedExtensions: ['jpg', 'jpeg', 'png', 'webp'],
                maxSizeBytes: 10 * 1024 * 1024,  // 10MB
                customBaseName: "{$type}-" . date('Y-m-d')
            );

            // Get access URL via Reader — adapter resolves signing internally
            // DO Spaces: returns presigned S3 GetObject URL (isSigned=true)
            // Local:     returns relative application-controlled path (isSigned=false)
            $accessResult = $this->imageReader->getAccessUrl($storedFile->path, expirationMinutes: 1440);

            return $this->json([
                'success' => true,
                'download_url' => $accessResult->url,        // Use directly
                'is_signed' => $accessResult->isSigned,
                'expires_in_seconds' => $accessResult->expirationSeconds
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

### Phase 5: Reader Services for Access URLs

For private files, use the Reader service to resolve access URLs. The Reader handles signing internally — no external signed URL generator needed:

```php
// Inject the Reader service alongside (or separately from) the Upload service
use Maatify\Storage\Services\ImageReaderPrivateService;

// In your controller:
$accessResult = $this->imageReader->getAccessUrl($storagePath, expirationMinutes: 60);

// DO Spaces adapter: $accessResult->url is a presigned S3 GetObject URL
// Local adapter:     $accessResult->url is a relative application-controlled path
echo $accessResult->url;             // Use directly — already signed if applicable
echo $accessResult->isSigned;        // true = presigned S3 URL, false = local path
echo $accessResult->expirationSeconds; // TTL in seconds
```

Reader services are registered in `MediaServiceProvider` (Phase 3) and available via DI:

### Phase 6: Testing

Run the test suite to verify integration:

```bash
# Full test suite (all tests should pass)
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php Modules/Storage/tests/Unit

# Or test specific service
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php Modules/Storage/tests/Unit/Services/AudioUploadServiceTest.php
```

### Phase 7: Security Verification

- [ ] All 12 environment variables are set and non-empty
- [ ] DigitalOcean API credentials are correct
- [ ] Public buckets have public-read ACL
- [ ] Private buckets have private ACL
- [ ] CDN is enabled on public buckets
- [ ] File upload size limits are enforced
- [ ] Allowed extensions are restricted to media types
- [ ] Signed URLs are generated with expiration times for private files

### Phase 8: Documentation

- [ ] Update project README with links to MULTI_DISK_GUIDE.md
- [ ] Add section to developer guide explaining service selection
- [ ] Document signed URL generation requirements
- [ ] Add examples to API documentation

## Validation Checklist

### Pre-Deployment Checks

- [ ] All tests pass: `phpunit` shows 102/102 ✅
- [ ] No PHP errors: `php -l` on all modified files
- [ ] Environment variables are set: `.env` contains all 12 configs
- [ ] Buckets exist in DigitalOcean with correct names
- [ ] CDN is working on public buckets
- [ ] API credentials have Spaces permissions
- [ ] File size limits are appropriate for your use case
- [ ] Signed URL generator is implemented for private files

### Post-Deployment Checks

- [ ] Upload public image → verify it's on CDN
- [ ] Upload private document → verify signed URL works
- [ ] Upload public video → verify CDN streaming works
- [ ] Upload private video → verify signed URL expiration works
- [ ] Upload public audio → verify CDN streaming works
- [ ] Upload private audio → verify signed URL works
- [ ] Test concurrent uploads to different services
- [ ] Test error handling (wrong extension, oversized file, etc.)

## Troubleshooting

### ConfigurationException: Missing Environment Variable

**Problem**: `PUBLIC_IMAGES_KEY` not set or empty

**Solution**:
```bash
# Verify variables are set
grep -E "PUBLIC_|PRIVATE_" .env | grep -v "^#" | sort

# Ensure no blank values
grep "=\s*$" .env
```

### InvalidFileException: Unsupported Extension

**Problem**: File extension is rejected even though it's in allowedExtensions

**Solution**: Probably a case sensitivity issue or extra spaces:
```php
// ❌ Wrong - spaces and case mismatch
allowedExtensions: [' jpg', 'JPEG']

// ✅ Correct - lowercase and no spaces
allowedExtensions: ['jpg', 'jpeg']
```

### File Upload Fails with No Error

**Problem**: MIME type validation failed silently

**Solution**: Check server logs for:
- File doesn't have valid magic bytes for the extension
- File is corrupted or incomplete
- Executable file disguised as media file

### Signed URLs Expire Too Quickly

**Problem**: Private files become inaccessible after short time

**Solution**: Increase expiration time:
```php
// 1 hour (recommended for temporary access)
$signedUrl = $generator->generate($path, 3600);

// 24 hours (for longer downloads)
$signedUrl = $generator->generate($path, 86400);

// 7 days (for persistent links)
$signedUrl = $generator->generate($path, 604800);
```

### CDN URLs Not Working

**Problem**: Public URLs return 404

**Solution**:
1. Verify bucket public-read ACL is enabled: `DO_SPACES_ACL=public-read`
2. Verify CDN is enabled in DigitalOcean console
3. Verify CDN URL in `.env` matches your bucket
4. Check file actually uploaded to bucket
5. Wait 5-10 minutes for CDN to cache

## Service Selection Guide

| Need | Service | Access | CDN |
|------|---------|--------|-----|
| Product images | `ImageUploadPublicService` | Direct | ✅ |
| User avatars | `ImageUploadPublicService` | Direct | ✅ |
| User documents | `ImageUploadPrivateService` | Signed URL | ❌ |
| Invoices/Receipts | `ImageUploadPrivateService` | Signed URL | ❌ |
| Tutorial videos | `VideoUploadPublicService` | Direct | ✅ |
| Marketing videos | `VideoUploadPublicService` | Direct | ✅ |
| Paid courses | `VideoUploadPrivateService` | Signed URL | ❌ |
| User recordings | `VideoUploadPrivateService` | Signed URL | ❌ |
| Podcasts | `AudioUploadPublicService` | Direct | ✅ |
| Audiobooks | `AudioUploadPublicService` | Direct | ✅ |
| Voice messages | `AudioUploadPrivateService` | Signed URL | ❌ |
| Meeting recordings | `AudioUploadPrivateService` | Signed URL | ❌ |

---

**For architectural details, see [MULTI_DISK_GUIDE.md](./MULTI_DISK_GUIDE.md)**

**For API reference, see [README.md](./README.md)**

**For test coverage, see [TESTING.md](./TESTING.md)**
