# Multi-Disk Storage Architecture Guide

## Overview

The Storage Module supports a **multi-disk architecture** for managing different media types with independent storage configurations, credentials, and access levels.

- **12 services** for DO Spaces: 6 upload + 6 reader (symmetric)
- **`FileServeService`** for local private storage: streams files outside the web root to the browser with Range support

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│           Application (Controllers, Services)               │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────┐
│         DI Container (MediaServiceProvider)                  │
│  Upload (6 services)  +  Reader (6 services)                │
│  FileServeService (local private, via registerServe())       │
└──────────────────────────────────────────────────────────────┘
    ↓                    ↓                    ↓
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   IMAGES     │    │   VIDEOS     │    │    AUDIO     │
├──────────────┤    ├──────────────┤    ├──────────────┤
│ Upload→Read  │    │ Upload→Read  │    │ Upload→Read  │
└──────────────┘    └──────────────┘    └──────────────┘
  ↓        ↓          ↓        ↓          ↓        ↓
 PUB    PRIV        PUB    PRIV        PUB    PRIV
  ↓        ↓          ↓        ↓          ↓        ↓
Upload  Upload     Upload  Upload     Upload  Upload
↓        ↓          ↓        ↓          ↓        ↓
Read   Read       Read   Read       Read   Read
  ↓        ↓          ↓        ↓          ↓        ↓
StoredFile URL      StoredFile URL      StoredFile URL
FileAccessResult URL FileAccessResult URL FileAccessResult URL
   ↓        ↓          ↓        ↓          ↓        ↓
   ┌──────────────────────────────────────────────────┐
   │  DigitalOcean Spaces (6 Separate Buckets)        │
   │                                                   │
   │  • public-images (public-read ACL + CDN)         │
   │  • private-images (private ACL, signed URLs)     │
   │  • public-videos (public-read ACL + CDN)         │
   │  • private-videos (private ACL, signed URLs)     │
   │  • public-audio (public-read ACL + CDN)          │
   │  • private-audio (private ACL, signed URLs)      │
   └──────────────────────────────────────────────────┘
```

## The 12 Services (Upload + Reader)

### Upload Services (6)

| Service | Access | CDN | Use Case |
|---------|--------|-----|----------|
| `ImageUploadPublicService` | Public | ✅ Yes | Product images, galleries |
| `ImageUploadPrivateService` | Private | ❌ No | Private images requiring authentication |
| `VideoUploadPublicService` | Public | ✅ Yes | Tutorials, courses, marketing |
| `VideoUploadPrivateService` | Private | ❌ No | Paid content, private recordings |
| `AudioUploadPublicService` | Public | ✅ Yes | Podcasts, audiobooks, music |
| `AudioUploadPrivateService` | Private | ❌ No | Voice messages, private recordings |

### Reader Services (6)

| Service | FileAccessResult URL | Use Case |
|---------|---------|----------|
| `ImageReaderPublicService` | Presigned S3 URL (DO Spaces) or relative path (Local) | Display public images, verify existence, get metadata |
| `ImageReaderPrivateService` | Presigned S3 URL (DO Spaces) or relative path (Local) | Access private images, get metadata |
| `VideoReaderPublicService` | Presigned S3 URL (DO Spaces) or relative path (Local) | Stream public videos, get metadata |
| `VideoReaderPrivateService` | Presigned S3 URL (DO Spaces) or relative path (Local) | Access private videos, get metadata |
| `AudioReaderPublicService` | Presigned S3 URL (DO Spaces) or relative path (Local) | Stream public audio, get metadata |
| `AudioReaderPrivateService` | Presigned S3 URL (DO Spaces) or relative path (Local) | Access private audio, get metadata |

## Setup Requirements

### 1. Environment Variables

Configure all 12 environment variables in your `.env` file:

```bash
# ========== PUBLIC IMAGES (CDN Accessible) ==========
PUBLIC_IMAGES_KEY=your-do-api-key
PUBLIC_IMAGES_SECRET=your-do-api-secret
PUBLIC_IMAGES_ENDPOINT=https://fra1.digitaloceanspaces.com
PUBLIC_IMAGES_BUCKET=my-public-images
PUBLIC_IMAGES_REGION=fra1
PUBLIC_IMAGES_CDN=https://my-public-images.fra1.cdn.digitaloceanspaces.com

# ========== PRIVATE IMAGES (Signed URLs) ==========
PRIVATE_IMAGES_KEY=your-do-api-key
PRIVATE_IMAGES_SECRET=your-do-api-secret
PRIVATE_IMAGES_ENDPOINT=https://fra1.digitaloceanspaces.com
PRIVATE_IMAGES_BUCKET=my-private-images
PRIVATE_IMAGES_REGION=fra1

# (and similarly for VIDEO and AUDIO...)
```

### 2. Register in DI Container

In your application bootstrap, call `StorageBindings::registerMultiDisk()`:

```php
use DI\ContainerBuilder;
use Maatify\Storage\Bootstrap\StorageBindings;
use Maatify\Storage\Bootstrap\MediaServiceProvider;

$containerBuilder = new ContainerBuilder();

// Register 12 upload + reader services (DO Spaces)
StorageBindings::registerMultiDisk($containerBuilder, $projectRoot);

// Optional: register FileServeService if any disk uses local storage
// Pass the absolute path where private local files are stored
MediaServiceProvider::registerServe($containerBuilder, '/var/www/storage/private');

$container = $containerBuilder->build();
```

> **Note:** `registerServe()` is only needed when files are stored **outside the web root** on the local filesystem. DO Spaces files are served via CDN or presigned URLs — no PHP streaming needed.

## Usage Examples

### Reading File Access URLs (Reader Services)

#### Get Image Access URL

```php
use Maatify\Storage\Services\ImageReaderPublicService;

class ProductController {
    public function __construct(
        private ImageReaderPublicService $imageReader
    ) {}

    public function getProductImage(ServerRequestInterface $request): void {
        $productId = $request->getAttribute('product_id');
        $imagePath = "products/product-{$productId}-abc123def456.jpg";

        // Get access URL/path from reader
        $accessResult = $this->imageReader->getAccessUrl($imagePath);
        echo "Image URL: " . $accessResult->url;  // Adapter-dependent:
                                                  // - DO Spaces: S3 presigned GetObject URL
                                                  // - Local: relative path for application access control
        
        // Check if image exists
        if ($this->imageReader->exists($imagePath)) {
            // Get metadata for auditing
            $metadata = $this->imageReader->getMetadata($imagePath);
            echo "Size: " . $metadata->sizeBytes . " bytes";
            echo "Type: " . $metadata->contentType;
        }
    }
}
```

#### Get Private Image Access URL

```php
use Maatify\Storage\Services\ImageReaderPrivateService;

class PrivateImageController {
    public function __construct(
        private ImageReaderPrivateService $imageReader
    ) {}

    public function getUserDocument(ServerRequestInterface $request): void {
        $userId = $request->getAttribute('user_id');
        $documentPath = "user-{$userId}/documents/invoice-abc123def456.jpg";

        // Check document exists
        if (!$this->imageReader->exists($documentPath)) {
            return response(404, 'Document not found');
        }

        // Get access URL/path from reader
        $accessResult = $this->imageReader->getAccessUrl($documentPath);
        $metadata = $this->imageReader->getMetadata($documentPath);
        
        // Use the access URL directly based on isSigned flag:
        // - if isSigned=true (DO Spaces): already a presigned S3 URL, use directly
        // - if isSigned=false (Local): relative path, application controls access
        
        return response()->json([
            'access_url' => $accessResult->url,      // Use this directly
            'is_signed' => $accessResult->isSigned,  // Check if already signed
            'size_bytes' => $metadata->sizeBytes
        ]);
    }
}
```

### Upload Public Product Image

```php
use Maatify\Storage\Services\ImageUploadPublicService;
use Psr\Http\Message\ServerRequestInterface;

class ProductController {
    public function __construct(
        private ImageUploadPublicService $imageService
    ) {}

    public function uploadImage(ServerRequestInterface $request): void {
        $file = $request->getUploadedFiles()['product_image'];
        $productId = $request->getAttribute('product_id');

        $storedFile = $this->imageService->upload(
            $file,
            'products',
            allowedExtensions: ['jpg', 'jpeg', 'png', 'webp'],
            maxSizeBytes: 5 * 1024 * 1024,  // 5MB
            customBaseName: "product-{$productId}"
        );

        // CDN URL is immediately accessible via ->url
        echo "Product image: " . $storedFile->url;
        
        // Storage path available for database storage
        $this->db->update('products', [
            'image_path' => $storedFile->path,
        ]);
    }
}
```

### Upload Private User Image

```php
use Maatify\Storage\Services\ImageUploadPrivateService;
use Psr\Http\Message\ServerRequestInterface;

class UserProfileController {
    public function __construct(
        private ImageUploadPrivateService $imageService
    ) {}

    public function uploadProfileImage(ServerRequestInterface $request): void {
        $file = $request->getUploadedFiles()['profile_image'];
        $userId = $request->getAttribute('user_id');

        $storedFile = $this->imageService->upload(
            $file,
            "user-{$userId}/images",
            allowedExtensions: ['jpg', 'jpeg', 'png', 'webp'],
            maxSizeBytes: 10 * 1024 * 1024,  // 10MB
            customBaseName: "profile-" . date('Y-m-d')
        );

        // Store the path in database for later signed URL generation
        $this->db->update('users', [
            'profile_image_path' => $storedFile->path,
        ]);
        
        // Generate signed URL for authenticated access
        $signedUrl = $this->generateSignedUrl($storedFile->path, expiresIn: 3600);
        echo "Temporary access: $signedUrl";
    }

    private function generateSignedUrl(string $path, int $expiresIn): string {
        // TODO: Implement using DigitalOcean Spaces signing
    }
}
```

### Upload Public Course Video

```php
use Maatify\Storage\Services\VideoUploadPublicService;

class CourseController {
    public function __construct(
        private VideoUploadPublicService $videoService
    ) {}

    public function uploadTutorial(ServerRequestInterface $request): void {
        $file = $request->getUploadedFiles()['video'];
        $courseId = $request->getAttribute('course_id');
        $lessonNumber = $request->getAttribute('lesson');

        $storedFile = $this->videoService->upload(
            $file,
            'tutorials',
            allowedExtensions: ['mp4', 'webm', 'mov'],
            maxSizeBytes: 500 * 1024 * 1024,  // 500MB
            customBaseName: "course-{$courseId}-lesson-{$lessonNumber}"
        );

        // Available on CDN immediately via ->url property
        return ['video_url' => $storedFile->url];
    }
}
```

### Upload Private Course Video

```php
use Maatify\Storage\Services\VideoUploadPrivateService;

class CourseController {
    public function __construct(
        private VideoUploadPrivateService $videoService
    ) {}

    public function uploadPaidContent(ServerRequestInterface $request): void {
        $file = $request->getUploadedFiles()['video'];
        $courseId = $request->getAttribute('course_id');
        $instructorId = $request->getAttribute('instructor_id');

        $storedFile = $this->videoService->upload(
            $file,
            "instructor-{$instructorId}/courses/{$courseId}",
            allowedExtensions: ['mp4', 'webm'],
            maxSizeBytes: 2 * 1024 * 1024 * 1024,  // 2GB
            customBaseName: "course-{$courseId}-video-" . time()
        );

        // Generate time-limited signed URL using storage path
        $signedUrl = $this->generateSignedUrl($storedFile->path, expiresIn: 3600);
        return ['video_url' => $signedUrl];
    }
}
```

### Upload & Read Public Podcast

#### Upload Episode

```php
use Maatify\Storage\Services\AudioUploadPublicService;

class PodcastController {
    public function __construct(
        private AudioUploadPublicService $audioService
    ) {}

    public function uploadEpisode(ServerRequestInterface $request): void {
        $file = $request->getUploadedFiles()['audio'];
        $episodeNumber = $request->getAttribute('episode');
        $season = $request->getAttribute('season');

        $storedFile = $this->audioService->upload(
            $file,
            'podcasts',
            allowedExtensions: ['mp3', 'wav', 'ogg'],
            customBaseName: "season-{$season}-episode-{$episodeNumber}"
        );

        // Store path in database for later retrieval
        return ['podcast_url' => $storedFile->url, 'path' => $storedFile->path];
    }
}
```

#### Read Episode Details

```php
use Maatify\Storage\Services\AudioReaderPublicService;

class PodcastListController {
    public function __construct(
        private AudioReaderPublicService $audioReader
    ) {}

    public function listEpisodes(): void {
        $episodes = $this->db->fetchAll('podcasts');
        
        foreach ($episodes as $episode) {
            // Get streaming URL from storage path
            $accessResult = $this->audioReader->getAccessUrl($episode['path']);
            
            // Get metadata (duration, size, format)
            $metadata = $this->audioReader->getMetadata($episode['path']);
            
            echo "Episode: " . $episode['title'];
            echo "Stream URL: " . $accessResult->url;
            echo "Size: " . $metadata->sizeBytes . " bytes";
            echo "Type: " . $metadata->contentType;
        }
    }
}
```

### Upload Private Voice Message

```php
use Maatify\Storage\Services\AudioUploadPrivateService;

class MessagingController {
    public function __construct(
        private AudioUploadPrivateService $audioService
    ) {}

    public function uploadVoiceMessage(ServerRequestInterface $request): void {
        $file = $request->getUploadedFiles()['audio'];
        $userId = $request->getAttribute('user_id');
        $conversationId = $request->getAttribute('conversation_id');

        $storedFile = $this->audioService->upload(
            $file,
            "user-{$userId}/messages/{$conversationId}",
            allowedExtensions: ['mp3', 'wav', 'ogg'],
            maxSizeBytes: 25 * 1024 * 1024,  // 25MB
            customBaseName: "message-" . time()
        );

        // Generate 1-hour expiring signed URL using storage path
        $signedUrl = $this->generateSignedUrl($storedFile->path, expiresIn: 3600);
        return ['audio_url' => $signedUrl];
    }
}
```

## Serving Local Private Files

When files are stored **outside the web root** (local private storage), use `FileServeService`
to stream them to the browser. The controller handles auth and builds the PSR-7 response.

```php
use Maatify\Storage\Http\LimitedResourceStream;
use Maatify\Storage\Services\FileServeService;
use Psr\Http\Message\ResponseFactoryInterface;

class PrivateVideoController
{
    public function __construct(
        private FileServeService         $serveService,
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function stream(ServerRequestInterface $request): ResponseInterface
    {
        // Your auth check here
        $path = $request->getAttribute('path'); // e.g. "courses/lesson-abc123.mp4"

        $result = $this->serveService->serve(
            $path,
            $request->getHeaderLine('Range') // pass Range header for video/audio seeking
        );

        // LimitedResourceStream caps the read to contentLength bytes —
        // the emitter will never send beyond rangeEnd, even on large files.
        $body = new LimitedResourceStream($result->stream, $result->contentLength);

        $response = $this->responseFactory->createResponse($result->isPartial ? 206 : 200)
            ->withHeader('Content-Type',   $result->mimeType)
            ->withHeader('Content-Length', (string) $result->contentLength)
            ->withHeader('Accept-Ranges',  'bytes')
            ->withHeader('ETag',           $result->eTag)
            ->withHeader('Last-Modified',  $result->lastModified->format('D, d M Y H:i:s \G\M\T'))
            ->withBody($body);

        if ($result->isPartial) {
            $response = $response->withHeader(
                'Content-Range',
                "bytes {$result->rangeStart}-{$result->rangeEnd}/{$result->size}"
            );
        }

        return $response;
    }
}
```

**Multiple local disks:** if you have separate paths per media type, instantiate directly:
```php
$imageServe = new FileServeService('/var/www/storage/private/images');
$videoServe = new FileServeService('/var/www/storage/private/videos');
$audioServe = new FileServeService('/var/www/storage/private/audio');
```

---

## Key Features

### 🔐 Security by Default

**MIME Type Validation via Magic Bytes**
- Prevents file spoofing attacks
- Reads actual file content, not just extension
- Supports: JPEG, PNG, WebP, GIF (images), MP4, WebM, MOV (videos), MP3, WAV, OGG (audio)

```php
// ✅ Real JPEG file with .jpg extension → PASSES
// ❌ Executable with .jpg extension → REJECTED (detected as executable)
```

**File Size Limits**
```php
// Reject files exceeding size limit
$storedFile = $imageService->upload($file, 'images', maxSizeBytes: 5 * 1024 * 1024);
// Returns StoredFile with $path and $url properties
```

**Extension Validation**
```php
// Only allow specific formats
$storedFile = $imageService->upload(
    $file,
    'images',
    allowedExtensions: ['jpg', 'jpeg', 'png']  // ✅ Type-safe
);
// Access URL via $storedFile->url, path via $storedFile->path
```

### 📝 Semantic Naming

Embed business context in filenames for compliance and traceability:

```php
// Before: images/image-abc123def456.jpg (generic)
// After: products/product-awesome-tier-abc123def456.jpg (semantic)

$storedFile = $imageService->upload(
    $file,
    'products',
    customBaseName: 'product-awesome-tier'
);
// Returns StoredFile with both path and url properties
```

### ⚡ CDN Integration

**Public Services**
```php
// Returns StoredFile with both path and url
$storedFile = $publicImageService->upload($file, 'gallery');

// $storedFile->path = gallery/image-abc123.jpg (storage key without slash)
// $storedFile->url  = https://cdn.example.com/gallery/image-abc123.jpg (CDN URL)

// Use URL directly for display
echo $storedFile->url;  // ✅ Ready for browser display
```

**Private Services**
```php
// Returns StoredFile with both path and url
$storedFile = $privateImageService->upload($file, 'private');

// $storedFile->path = private/image-2024-01-15-abc123.jpg  (storage key — store in DB)
// $storedFile->url  = adapter-constructed URL (may not be directly accessible for private ACL)

// Store path for later retrieval via Reader
$db->insert('files', ['storage_path' => $storedFile->path]);

// Later: use Reader to get time-limited access URL
// $accessResult = $privateImageReader->getAccessUrl($storedFile->path);
// DO Spaces: $accessResult->url is a presigned S3 GetObject URL (isSigned=true)
// Local:     $accessResult->url is a relative application-controlled path (isSigned=false)
```

**Key Differences:**
- `$storedFile->path`: Raw storage key (no leading slash) — persist in DB, use with Reader for access
- `$storedFile->url`: Adapter-constructed URL — use directly for public uploads; for private uploads, prefer Reader to get a properly resolved access URL

### 🔄 Automatic Uniqueness

All filenames include a random 16-character hex suffix to prevent overwrites:

```php
// Multiple uploads with same customBaseName = unique files
$storedFile1 = $imageService->upload($file1, 'images', customBaseName: 'user-avatar');
// Result: $storedFile1->path = images/user-avatar-abc123def456.jpg

$storedFile2 = $imageService->upload($file2, 'images', customBaseName: 'user-avatar');
// Result: $storedFile2->path = images/user-avatar-xyz789uvw012.jpg

// Each upload gets a unique random suffix to prevent overwrites
```

## Configuration Validation

StorageConfigs validates all required environment variables on first use:

```php
try {
    $config = StorageConfigs::publicImagesConfig();
} catch (ConfigurationException $e) {
    // Missing required environment variable
    echo "Error: " . $e->getMessage();
}
```

Error messages clearly indicate which environment variable is missing and why:

```
Error: Missing environment variable PUBLIC_IMAGES_KEY
Configure public images storage: set PUBLIC_IMAGES_KEY to API key
```

## DigitalOcean Spaces Setup

### Create 6 Buckets

1. **my-public-images** (public-read ACL, CDN enabled)
2. **my-private-images** (private ACL, no CDN)
3. **my-public-videos** (public-read ACL, CDN enabled)
4. **my-private-videos** (private ACL, no CDN)
5. **my-public-audio** (public-read ACL, CDN enabled)
6. **my-private-audio** (private ACL, no CDN)

### Generate API Keys

1. Go to API → Tokens → Spaces Keys
2. Create application keys with appropriate permissions
3. Copy Key and Secret
4. Assign to corresponding environment variables

### Enable CDN (Public Buckets Only)

1. Go to Settings → CORS & CDN
2. Enable CDN distribution
3. Copy CDN URL to environment variable

## Testing

Run the complete test suite:

```bash
./vendor/bin/phpunit --bootstrap Modules/Storage/tests/bootstrap.php Modules/Storage/tests/Unit
```

## Examples

See `examples/MediaController.example.php` for a complete working example demonstrating all 6 services.

## Performance Considerations

- **MIME Type Validation**: O(1) complexity, reads first 512 bytes only
- **File Size Check**: O(1) complexity, checks uploaded file size
- **Upload Time**: Depends on network and file size, not module implementation
- **CDN Caching**: Public URLs are cached globally, private URLs use short-lived signatures

## Troubleshooting

### Missing Environment Variables

```php
ConfigurationException: Missing environment variable PUBLIC_IMAGES_KEY
```

**Solution**: Ensure all 12 environment variables are set in `.env`:

```bash
grep "PUBLIC_IMAGES\|PRIVATE_IMAGES\|PUBLIC_VIDEOS\|PRIVATE_VIDEOS\|PUBLIC_AUDIO\|PRIVATE_AUDIO" .env
```

### Authentication Failures

```php
InvalidFileException: Unsupported file type
```

**Solution**: File failed MIME type validation. Ensure:
1. File has valid magic bytes (not corrupted)
2. File extension matches actual content
3. File size doesn't exceed limit

### Injection Attacks

```php
InvalidFileException: Unsupported extension: exe
```

**Solution**: Extensions are validated and sanitized. This protection is working.

## Security Notes

✅ **What This Module Protects Against**
- File spoofing (executable as JPEG)
- Path traversal attacks
- Filename injection attacks
- Overwrite attacks (random suffixes)

⚠️ **What You Must Handle**
- Authentication for private files (signed URLs)
- Rate limiting on uploads
- Disk space quotas
- Malware scanning (optional)
- GDPR compliance for stored data

## API Stability

All public APIs are stable and follow semantic versioning. Version 1.3.0+ is production-ready.

## Migration Path

Switching from single-storage to multi-disk:

```php
// Before: Single storage configuration
StorageBindings::register($builder, $rootPath, $config);

// After: 6 independent services
StorageBindings::registerMultiDisk($builder, $rootPath);
```

No code changes required in controllers — just inject the specific service you need.

---

**For detailed testing information, see [TESTING.md](./TESTING.md)**

**For core module documentation, see [README.md](./README.md)**
