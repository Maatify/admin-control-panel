<?php

declare(strict_types=1);

/**
 * Storage Module - Usage Examples
 *
 * This file demonstrates how to use the Storage module with different configurations
 * and services.
 */

use DI\ContainerBuilder;
use Maatify\Storage\Bootstrap\StorageBindings;
use Maatify\Storage\Bootstrap\MediaServiceProvider;
use Maatify\Storage\Config\DOSpacesConfig;
use Maatify\Storage\Config\ImageDimensions;
use Maatify\Storage\Config\StorageConfig;
use Maatify\Storage\Services\AudioUploadService;
use Maatify\Storage\Services\ImageUploadService;
use Maatify\Storage\Services\VideoUploadService;
use Maatify\Storage\Services\AudioReaderService;
use Maatify\Storage\Services\ImageReaderService;
use Maatify\Storage\Services\VideoReaderService;
use Psr\Container\ContainerInterface;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';

// ═══════════════════════════════════════════════════════════════════════════════
// SECURITY NOTE: MIME Type Validation (Enabled by Default)
// ═══════════════════════════════════════════════════════════════════════════════
//
// All image and video uploads automatically validate MIME type by inspecting
// file content (magic bytes), not just file extension.
//
// This prevents file spoofing attacks like:
// - virus.jpg (actually an executable → REJECTED)
// - trojan.png (actually a Windows PE binary → REJECTED)
// - script.jpg (actually a shell script → REJECTED)
//
// You don't need to do anything - it's secure by default!
// $url = $imageService->upload($file, 'images');  // ✅ Secure
//
// ═══════════════════════════════════════════════════════════════════════════════
// 1. SETUP: Choose Your Storage Configuration
// ═══════════════════════════════════════════════════════════════════════════════

$containerBuilder = new ContainerBuilder();

// Option A: From environment variables
$config = StorageConfig::fromEnv($_ENV);

// Option B: Local filesystem (hardcoded)
// $config = new StorageConfig(driver: 'local');

// Option C: DigitalOcean Spaces (hardcoded)
// $config = new StorageConfig(
//     driver: 'do_spaces',
//     doSpaces: new DOSpacesConfig(
//         key:      'your_do_spaces_key',
//         secret:   'your_do_spaces_secret',
//         endpoint: 'https://fra1.digitaloceanspaces.com',
//         bucket:   'my-bucket',
//         region:   'fra1',
//         cdnUrl:   'https://my-bucket.fra1.cdn.digitaloceanspaces.com',
//     ),
// );

StorageBindings::register($containerBuilder, APP_ROOT, $config);
$container = $containerBuilder->build();

// ═══════════════════════════════════════════════════════════════════════════════
// 2. IMAGE UPLOADS - Simple Usage (All Defaults)
// ═══════════════════════════════════════════════════════════════════════════════

/** @var ImageUploadService $imageService */
$imageService = $container->get(ImageUploadService::class);

// Example: Upload a product image with all defaults (jpg, jpeg, png, webp up to 5MB)
// $url = $imageService->upload($uploadedFile, 'products');

// ═══════════════════════════════════════════════════════════════════════════════
// 3. IMAGE UPLOADS - Custom Size Limit
// ═══════════════════════════════════════════════════════════════════════════════

// Allow larger images (e.g., 20MB) but keep default extensions
// $url = $imageService->upload(
//     $uploadedFile,
//     'gallery',
//     null,  // use default extensions (jpg, jpeg, png, webp)
//     20 * 1024 * 1024  // 20MB
// );

// ═══════════════════════════════════════════════════════════════════════════════
// 4. IMAGE UPLOADS - Custom Extensions
// ═══════════════════════════════════════════════════════════════════════════════

// Allow only JPEG and PNG, use default size limit
// $url = $imageService->upload(
//     $uploadedFile,
//     'avatars',
//     ['jpg', 'jpeg', 'png'],  // custom extensions
//     null  // keep default 5MB limit
// );

// ═══════════════════════════════════════════════════════════════════════════════
// 5. IMAGE UPLOADS - With Dimension Constraints
// ═══════════════════════════════════════════════════════════════════════════════

// Validate image dimensions (profile pictures: 50x50 to 500x500)
// $url = $imageService->upload(
//     $uploadedFile,
//     'profiles',
//     null,  // use default extensions
//     3 * 1024 * 1024,  // 3MB
//     new ImageDimensions(
//         minWidth:  50,
//         minHeight: 50,
//         maxWidth:  500,
//         maxHeight: 500,
//     ),
//     null  // use default filename generation
// );

// ═══════════════════════════════════════════════════════════════════════════════
// 6. IMAGE UPLOADS - With Custom Semantic Basename
// ═══════════════════════════════════════════════════════════════════════════════

// Use custom basename for semantic naming (e.g., product slug, entity code)
// This preserves business context in the filename for traceability
// $url = $imageService->upload(
//     $uploadedFile,
//     'products',
//     null,  // use default extensions
//     null,  // use default 5MB limit
//     null,  // no dimension validation
//     'product-slug-123'  // custom basename: results in "product-slug-123-abc123def456.jpg"
// );

// ═══════════════════════════════════════════════════════════════════════════════
// 7. VIDEO UPLOADS - Simple Usage (All Defaults)
// ═══════════════════════════════════════════════════════════════════════════════

/** @var VideoUploadService $videoService */
$videoService = $container->get(VideoUploadService::class);

// Example: Upload a video with all defaults (mp4, webm, mov, avi up to 500MB)
// $url = $videoService->upload($uploadedFile, 'testimonials');

// ═══════════════════════════════════════════════════════════════════════════════
// 8. VIDEO UPLOADS - Custom Size Limit
// ═══════════════════════════════════════════════════════════════════════════════

// Allow larger videos (e.g., 2GB) but keep default extensions
// $url = $videoService->upload(
//     $uploadedFile,
//     'tutorials',
//     null,  // use default extensions
//     2 * 1024 * 1024 * 1024  // 2GB
// );

// ═══════════════════════════════════════════════════════════════════════════════
// 9. VIDEO UPLOADS - MP4 Only
// ═══════════════════════════════════════════════════════════════════════════════

// Allow only MP4 videos
// $url = $videoService->upload(
//     $uploadedFile,
//     'courses',
//     ['mp4'],  // only MP4
//     1024 * 1024 * 1024  // 1GB
// );

// ═══════════════════════════════════════════════════════════════════════════════
// 10. VIDEO UPLOADS - With Custom Semantic Basename
// ═══════════════════════════════════════════════════════════════════════════════

// Use custom basename for semantic naming (e.g., webinar ID, course slug)
// $url = $videoService->upload(
//     $uploadedFile,
//     'webinars',
//     null,  // use default extensions
//     null,  // no size validation
//     'webinar-123-live'  // custom basename: results in "webinar-123-live-abc123def456.mp4"
// );

// ═══════════════════════════════════════════════════════════════════════════════
// 11. AUDIO UPLOADS - Simple Usage (All Defaults)
// ═══════════════════════════════════════════════════════════════════════════════

/** @var AudioUploadService $audioService */
$audioService = $container->get(AudioUploadService::class);

// Example: Upload a podcast episode with all defaults (mp3, wav, ogg)
// $url = $audioService->upload($uploadedFile, 'podcasts');

// ═══════════════════════════════════════════════════════════════════════════════
// 12. AUDIO UPLOADS - Custom Size Limit
// ═══════════════════════════════════════════════════════════════════════════════

// Allow larger audio files (e.g., 100MB) but keep default extensions
// $url = $audioService->upload(
//     $uploadedFile,
//     'audiobooks',
//     null,  // use default extensions (mp3, wav, ogg)
//     100 * 1024 * 1024  // 100MB
// );

// ═══════════════════════════════════════════════════════════════════════════════
// 13. AUDIO UPLOADS - With Custom Semantic Basename
// ═══════════════════════════════════════════════════════════════════════════════

// Use custom basename for semantic naming (e.g., podcast episode, dua name)
// $url = $audioService->upload(
//     $uploadedFile,
//     'podcasts',
//     null,  // use default extensions
//     null,  // no size validation
//     'episode-15-understanding-quran'  // custom basename: results in "episode-15-understanding-quran-abc123def456.mp3"
// );

// ═══════════════════════════════════════════════════════════════════════════════
// 14. ADVANCED: Custom Storage Configurations (Multiple Buckets)
// ═══════════════════════════════════════════════════════════════════════════════
//
// For projects with multiple storage backends (public, private, media, backups, cdn),
// implement StorageConfigInterface for each scenario:
//
// Example: PublicStorageConfig
// ─────────────────────────────
// class PublicStorageConfig implements StorageConfigInterface {
//     public function getDriver(): string { return 'do_spaces'; }
//     public function getLocalConfig(): ?LocalStorageConfig { return null; }
//     public function getDoSpacesConfig(): ?DOSpacesConfig {
//         return new DOSpacesConfig(
//             key: $_ENV['PUBLIC_BUCKET_KEY'],
//             secret: $_ENV['PUBLIC_BUCKET_SECRET'],
//             endpoint: $_ENV['DO_SPACES_ENDPOINT'],
//             bucket: $_ENV['PUBLIC_BUCKET'],
//             region: $_ENV['DO_SPACES_REGION'],
//             cdnUrl: $_ENV['PUBLIC_BUCKET_CDN'],
//             acl: 'public-read'
//         );
//     }
// }
//
// Example: PrivateStorageConfig
// ──────────────────────────────
// class PrivateStorageConfig implements StorageConfigInterface {
//     public function getDriver(): string { return 'do_spaces'; }
//     public function getLocalConfig(): ?LocalStorageConfig { return null; }
//     public function getDoSpacesConfig(): ?DOSpacesConfig {
//         return new DOSpacesConfig(
//             key: $_ENV['PRIVATE_BUCKET_KEY'],
//             secret: $_ENV['PRIVATE_BUCKET_SECRET'],
//             endpoint: $_ENV['DO_SPACES_ENDPOINT'],
//             bucket: $_ENV['PRIVATE_BUCKET'],
//             region: $_ENV['DO_SPACES_REGION'],
//             cdnUrl: $_ENV['PRIVATE_BUCKET_CDN'],
//             acl: 'private'
//         );
//     }
// }
//
// Example: StorageManager (to manage multiple configs)
// ────────────────────────────────────────────────────
// class StorageManager {
//     private array $adapters = [];
//
//     public function __construct(
//         private AppPaths $paths,
//         private array $configs
//     ) {}
//
//     public function disk(string $name): StorageAdapterInterface {
//         if (!isset($this->adapters[$name])) {
//             $config = $this->configs[$name]
//                 ?? throw new RuntimeException("Disk '{$name}' not configured");
//             $this->adapters[$name] = StorageAdapterFactory::create($this->paths, $config);
//         }
//         return $this->adapters[$name];
//     }
// }
//
// Usage in Controllers:
// ─────────────────────
// // Get adapter and wrap in service for upload() with validation
//
// // Upload to public storage with semantic naming
// $adapter = $storageManager->disk('public');
// $imageService = new ImageUploadService(new FileUploadService($adapter));
// $url = $imageService->upload($file, 'images/products', customBaseName: $productSlug);
//
// // Upload to private storage
// $adapter = $storageManager->disk('private');
// $fileService = new FileUploadService($adapter);
// // FileUploadService builds path directly (no customBaseName parameter)
// $semanticPath = "documents/invoices/invoice-{$invoiceId}-" . bin2hex(random_bytes(8)) . '.pdf';
// $url = $fileService->upload($file, $semanticPath);
//
// // Upload to media storage (videos)
// $adapter = $storageManager->disk('media');
// $videoService = new VideoUploadService(new FileUploadService($adapter));
// $url = $videoService->upload($file, 'videos/tutorials', customBaseName: "tutorial-{$tutorialId}");
//
// See EXTENSION_GUIDE.md for complete implementation examples!
//
// ═══════════════════════════════════════════════════════════════════════════════
// 15. READING FILES - Image Reader (Get Access URL)
// ═════════════════════════════════════════════════════════════════════════════════

/** @var ImageReaderService $imageReader */
$imageReader = $container->get(ImageReaderService::class);

// Example: Get access URL/path for a stored image
// $accessResult = $imageReader->getAccessUrl('products/product-awesome-abc123def456.jpg');
// echo $accessResult->url;       // Adapter-dependent:
//                                // - DO Spaces: S3 presigned GetObject URL
//                                // - Local: relative path for application access control
// echo $accessResult->isSigned;  // true = already signed, false = application controls access
// echo $accessResult->expiresAt; // DateTimeImmutable object with expiration

// ═══════════════════════════════════════════════════════════════════════════════
// 16. READING FILES - Check File Existence
// ═════════════════════════════════════════════════════════════════════════════════

// Check if a file exists in storage (returns false for directories)
// $exists = $imageReader->exists('products/product-awesome-abc123def456.jpg');
// if ($exists) {
//     echo "File found!";
// } else {
//     echo "File not found!";
// }

// ═══════════════════════════════════════════════════════════════════════════════
// 17. READING FILES - Get File Metadata
// ═════════════════════════════════════════════════════════════════════════════════

// Get file metadata (size, content type, last modified time)
// $metadata = $imageReader->getMetadata('products/product-awesome-abc123def456.jpg');
// echo $metadata->sizeBytes;      // 245120 (bytes)
// echo $metadata->contentType;    // image/jpeg
// echo $metadata->lastModified;   // DateTimeImmutable object
// echo $metadata->eTag;           // Entity tag (null for local, set for DO Spaces)

// ═══════════════════════════════════════════════════════════════════════════════
// 18. VIDEO READER - Get Video Access URL & Metadata
// ═════════════════════════════════════════════════════════════════════════════════

/** @var VideoReaderService $videoReader */
$videoReader = $container->get(VideoReaderService::class);

// Get video streaming URL and metadata
// $accessResult = $videoReader->getAccessUrl('tutorials/tutorial-advanced-php-abc123def456.mp4');
// echo $accessResult->url;  // Adapter-dependent: presigned S3 URL (DO Spaces) or relative path (Local)

// Get video file metadata for player info
// $metadata = $videoReader->getMetadata('tutorials/tutorial-advanced-php-abc123def456.mp4');
// echo "Video size: " . $metadata->sizeBytes . " bytes";
// echo "Format: " . $metadata->contentType;  // video/mp4

// ═══════════════════════════════════════════════════════════════════════════════
// 19. AUDIO READER - Get Podcast Access URL & Metadata
// ═════════════════════════════════════════════════════════════════════════════════

/** @var AudioReaderService $audioReader */
$audioReader = $container->get(AudioReaderService::class);

// Get podcast streaming URL
// $accessResult = $audioReader->getAccessUrl('podcasts/episode-15-understanding-quran-abc123def456.mp3');
// echo $accessResult->url;  // Adapter-dependent: presigned S3 URL (DO Spaces) or relative path (Local)

// Get audio metadata
// $metadata = $audioReader->getMetadata('podcasts/episode-15-understanding-quran-abc123def456.mp3');
// echo "Duration info: " . $metadata->sizeBytes . " bytes";
// echo "Format: " . $metadata->contentType;  // audio/mpeg

// ═══════════════════════════════════════════════════════════════════════════════
// 20. COMPLETE UPLOAD + READ LIFECYCLE
// ═════════════════════════════════════════════════════════════════════════════════

// Step 1: Upload an image with semantic naming
// $storedFile = $imageService->upload(
//     $uploadedFile,
//     'products',
//     customBaseName: 'awesome-product'
// );
// // Result: $storedFile->path = products/awesome-product-abc123def456.jpg
// // Result: $storedFile->url = https://cdn.example.com/products/awesome-product-abc123def456.jpg
//
// // Store the path in database for later retrieval
// $db->insert('products', [
//     'image_path' => $storedFile->path,
//     'image_url' => $storedFile->url
// ]);

// Step 2: Later, retrieve the image information using Reader
// $storagePath = $db->fetch('products', 'image_path');
//
// // Check if file still exists
// if (!$imageReader->exists($storagePath)) {
//     throw new Exception('Image file was deleted');
// }
//
// // Get updated access URL and metadata
// $accessResult = $imageReader->getAccessUrl($storagePath);
// $metadata = $imageReader->getMetadata($storagePath);
//
// // Return to client
// return [
//     'url' => $accessResult->url,
//     'size' => $metadata->sizeBytes,
//     'type' => $metadata->contentType
// ];

// ═══════════════════════════════════════════════════════════════════════════════
// 21. PRIVATE FILE HANDLING (Upload + Reader Access)
// ═════════════════════════════════════════════════════════════════════════════════

// Upload always returns StoredFile with both path and url.
// For private buckets, store $storedFile->path in your database and use
// the Reader service later to resolve the access URL when needed.

// Step 1: Upload to private storage
// $storedFile = $privateImageService->upload(
//     $uploadedFile,
//     "user-{$userId}/documents",
//     customBaseName: 'invoice-' . date('Y-m-d')
// );
// // $storedFile->path = user-123/documents/invoice-2024-01-15-abc123def456.jpg  (storage key)
// // $storedFile->url  = adapter-resolved URL (may not be directly accessible for private ACL)

// Step 2: Store path in database
// $db->insert('invoices', [
//     'storage_path' => $storedFile->path
// ]);

// Step 3: Later, when user wants to download, get access URL from reader
// $storagePath = $db->fetch('invoices', 'storage_path');
//
// // First, verify file exists
// if (!$privateImageReader->exists($storagePath)) {
//     throw new Exception('Invoice file not found');
// }
//
// // Get access URL/path from reader
// $accessResult = $privateImageReader->getAccessUrl($storagePath);
// $metadata = $privateImageReader->getMetadata($storagePath);
//
// // Use the access URL directly based on isSigned flag:
// // - if isSigned=true (DO Spaces): already a presigned S3 URL, use directly
// // - if isSigned=false (Local): relative path, application implements access control
//
// return [
//     'access_url' => $accessResult->url,      // Use this directly
//     'is_signed' => $accessResult->isSigned,  // Check if already signed
//     'file_size' => $metadata->sizeBytes
// ];

// ═══════════════════════════════════════════════════════════════════════════════
// SUMMARY: Complete Upload + Read Lifecycle
// ═════════════════════════════════════════════════════════════════════════════════
//
// UPLOAD SIDE (Write Operations):
// ──────────────────────────────
// ImageUploadService::upload():
//   - $allowedExtensions = null  → use defaults (jpg, jpeg, png, webp)
//   - $maxSizeBytes = null       → no size validation
//   - $dimensions = null         → no dimension validation
//   - $customBaseName = null     → auto-generate from original filename
//   - Returns: StoredFile with path and url properties
//
// VideoUploadService::upload(), AudioUploadService::upload():
//   - Similar pattern to ImageUploadService
//   - Returns: StoredFile with path and url properties
//
// READER SIDE (Read Operations):
// ──────────────────────────────
// ImageReaderService, VideoReaderService, AudioReaderService all provide three methods:
//
// 1. getAccessUrl(string $path, int $expirationMinutes = 60): FileAccessResult
//    Returns: url (adapter-dependent), path, isSigned, expiresAt, expirationSeconds
//    DO Spaces: url is a presigned S3 GetObject URL (isSigned=true)
//    Local:     url is a relative application-controlled path (isSigned=false)
//    Use for: Getting the access URL for a stored file
//
// 2. exists(string $path): bool
//    Returns: true if file exists, false otherwise (directories return false)
//    Use for: Verify file existence before accessing
//
// 3. getMetadata(string $path): FileMetadata
//    Returns: sizeBytes, contentType, lastModified, eTag
//    Use for: Getting file info for UI, logging, audit trails
//    Throws: FileNotFoundException if file doesn't exist
//
// SEMANTIC NAMING:
// Pass $customBaseName to preserve business context in filenames:
//   - "product-slug-abc123def456.jpg" (not just "image-abc123def456.jpg")
//   - "payment-method-123-abc123def456.jpg" (encodes payment method ID)
//   - "shipping-method-456-lang-1-abc123def456.jpg" (encodes method and language)
//
// Benefits:
//   - Traceability: understand what entity each file belongs to
//   - Organization: semantically grouped files in storage
//   - Compliance: meaningful context for audit trails
//
// COMPLETE LIFECYCLE:
//   1. UPLOAD: $storedFile = $imageService->upload($file, 'folder', customBaseName: $name);
//   2. STORE:  db->insert('table', ['path' => $storedFile->path]);
//   3. VERIFY: if ($imageReader->exists($storagePath)) { ... }
//   4. READ:   $accessResult = $imageReader->getAccessUrl($storagePath);
//             // DO Spaces: presigned S3 URL (isSigned=true)
//             // Local:     relative application-controlled path (isSigned=false)
//   5. META:  $metadata = $imageReader->getMetadata($storagePath);
//
// UPLOAD StoredFile vs READER FileAccessResult:
//   - Upload → StoredFile: {path, url} where url is the adapter-constructed storage URL
//   - Reader → FileAccessResult: {url, path, isSigned, expiresAt, expirationSeconds}
//     where url format is adapter-dependent (not service-type-dependent)
//
// FLEXIBILITY:
//   - Simple case: just upload($file, $folder)
//   - With constraints: add what you need, leave the rest null
//   - With readers: verify existence, get metadata, get access URL
//
// EXTENSIBILITY:
//   - Simple projects: use default StorageConfig::fromEnv()
//   - Complex projects: implement StorageConfigInterface for each storage scenario
//   - No code changes needed: module adapts to your needs!
//
// ═════════════════════════════════════════════════════════════════════════════════
