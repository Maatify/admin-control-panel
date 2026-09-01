<?php

declare(strict_types=1);

namespace Maatify\Storage\Config;

use Maatify\Storage\Contracts\StorageConfigInterface;
use Maatify\Storage\Exception\ConfigurationException;

/**
 * Storage configurations for all media types and access levels.
 *
 * Provides factory methods for creating StorageConfig instances with pre-configured
 * DigitalOcean Spaces buckets for:
 * - Public Images (CDN enabled)
 * - Private Images (authenticated access)
 * - Public Videos (CDN enabled)
 * - Private Videos (authenticated access)
 * - Public Audio (CDN enabled)
 * - Private Audio (authenticated access)
 *
 * ✅ Public configs: have CDN URLs for direct browser access
 * 🔒 Private configs: no CDN (authenticated/signed URLs only)
 *
 * @throws ConfigurationException If required environment variables are missing
 */
final class StorageConfigs
{
    /**
     * Validate that required environment variables exist.
     *
     * @param string $prefix Environment variable prefix (e.g., "PUBLIC_IMAGES")
     * @return void
     * @throws ConfigurationException If any required variable is missing
     */
    private static function validateEnvVars(string $prefix): void
    {
        $requiredVars = [
            "{$prefix}_KEY",
            "{$prefix}_SECRET",
            "{$prefix}_ENDPOINT",
            "{$prefix}_BUCKET",
            "{$prefix}_REGION",
        ];

        // CDN URL is required only for public configs
        if (strpos($prefix, 'PUBLIC_') === 0) {
            $requiredVars[] = "{$prefix}_CDN";
        }

        foreach ($requiredVars as $varName) {
            if (empty($_ENV[$varName] ?? null)) {
                throw ConfigurationException::missingEnvVariable($varName);
            }
        }
    }
    /**
     * Public images configuration.
     *
     * - Bucket: PUBLIC_IMAGES_BUCKET
     * - ACL: public-read
     * - CDN: PUBLIC_IMAGES_CDN
     * - Use case: Product images, public galleries
     *
     * @return StorageConfigInterface
     * @throws ConfigurationException If required environment variables are missing
     */
    public static function publicImagesConfig(): StorageConfigInterface
    {
        self::validateEnvVars('PUBLIC_IMAGES');

        return new StorageConfig(
            driver: 'do_spaces',
            doSpaces: new DOSpacesConfig(
                key: $_ENV['PUBLIC_IMAGES_KEY'],
                secret: $_ENV['PUBLIC_IMAGES_SECRET'],
                endpoint: $_ENV['PUBLIC_IMAGES_ENDPOINT'],
                bucket: $_ENV['PUBLIC_IMAGES_BUCKET'],
                region: $_ENV['PUBLIC_IMAGES_REGION'],
                cdnUrl: $_ENV['PUBLIC_IMAGES_CDN'],
                acl: 'public-read'
            ),
        );
    }

    /**
     * Private images configuration.
     *
     * - Bucket: PRIVATE_IMAGES_BUCKET
     * - ACL: private
     * - CDN: disabled (use signed URLs)
     * - Use case: User documents, invoices, private uploads
     *
     * @return StorageConfigInterface
     * @throws ConfigurationException If required environment variables are missing
     */
    public static function privateImagesConfig(): StorageConfigInterface
    {
        self::validateEnvVars('PRIVATE_IMAGES');

        return new StorageConfig(
            driver: 'do_spaces',
            doSpaces: new DOSpacesConfig(
                key: $_ENV['PRIVATE_IMAGES_KEY'],
                secret: $_ENV['PRIVATE_IMAGES_SECRET'],
                endpoint: $_ENV['PRIVATE_IMAGES_ENDPOINT'],
                bucket: $_ENV['PRIVATE_IMAGES_BUCKET'],
                region: $_ENV['PRIVATE_IMAGES_REGION'],
                cdnUrl: null,  // ❌ No CDN for private access
                acl: 'private'
            ),
        );
    }

    /**
     * Public videos configuration.
     *
     * - Bucket: PUBLIC_VIDEOS_BUCKET
     * - ACL: public-read
     * - CDN: PUBLIC_VIDEOS_CDN
     * - Use case: Tutorials, public courses, marketing videos
     *
     * @return StorageConfigInterface
     * @throws ConfigurationException If required environment variables are missing
     */
    public static function publicVideosConfig(): StorageConfigInterface
    {
        self::validateEnvVars('PUBLIC_VIDEOS');

        return new StorageConfig(
            driver: 'do_spaces',
            doSpaces: new DOSpacesConfig(
                key: $_ENV['PUBLIC_VIDEOS_KEY'],
                secret: $_ENV['PUBLIC_VIDEOS_SECRET'],
                endpoint: $_ENV['PUBLIC_VIDEOS_ENDPOINT'],
                bucket: $_ENV['PUBLIC_VIDEOS_BUCKET'],
                region: $_ENV['PUBLIC_VIDEOS_REGION'],
                cdnUrl: $_ENV['PUBLIC_VIDEOS_CDN'],
                acl: 'public-read'
            ),
        );
    }

    /**
     * Private videos configuration.
     *
     * - Bucket: PRIVATE_VIDEOS_BUCKET
     * - ACL: private
     * - CDN: disabled (use signed URLs)
     * - Use case: Paid courses, user-generated content, private recordings
     *
     * @return StorageConfigInterface
     * @throws ConfigurationException If required environment variables are missing
     */
    public static function privateVideosConfig(): StorageConfigInterface
    {
        self::validateEnvVars('PRIVATE_VIDEOS');

        return new StorageConfig(
            driver: 'do_spaces',
            doSpaces: new DOSpacesConfig(
                key: $_ENV['PRIVATE_VIDEOS_KEY'],
                secret: $_ENV['PRIVATE_VIDEOS_SECRET'],
                endpoint: $_ENV['PRIVATE_VIDEOS_ENDPOINT'],
                bucket: $_ENV['PRIVATE_VIDEOS_BUCKET'],
                region: $_ENV['PRIVATE_VIDEOS_REGION'],
                cdnUrl: null,  // ❌ No CDN for private access
                acl: 'private'
            ),
        );
    }

    /**
     * Public audio configuration.
     *
     * - Bucket: PUBLIC_AUDIO_BUCKET
     * - ACL: public-read
     * - CDN: PUBLIC_AUDIO_CDN
     * - Use case: Podcasts, public music, audiobooks
     *
     * @return StorageConfigInterface
     * @throws ConfigurationException If required environment variables are missing
     */
    public static function publicAudioConfig(): StorageConfigInterface
    {
        self::validateEnvVars('PUBLIC_AUDIO');

        return new StorageConfig(
            driver: 'do_spaces',
            doSpaces: new DOSpacesConfig(
                key: $_ENV['PUBLIC_AUDIO_KEY'],
                secret: $_ENV['PUBLIC_AUDIO_SECRET'],
                endpoint: $_ENV['PUBLIC_AUDIO_ENDPOINT'],
                bucket: $_ENV['PUBLIC_AUDIO_BUCKET'],
                region: $_ENV['PUBLIC_AUDIO_REGION'],
                cdnUrl: $_ENV['PUBLIC_AUDIO_CDN'],
                acl: 'public-read'
            ),
        );
    }

    /**
     * Private audio configuration.
     *
     * - Bucket: PRIVATE_AUDIO_BUCKET
     * - ACL: private
     * - CDN: disabled (use signed URLs)
     * - Use case: Voice messages, private recordings, user audio files
     *
     * @return StorageConfigInterface
     * @throws ConfigurationException If required environment variables are missing
     */
    public static function privateAudioConfig(): StorageConfigInterface
    {
        self::validateEnvVars('PRIVATE_AUDIO');

        return new StorageConfig(
            driver: 'do_spaces',
            doSpaces: new DOSpacesConfig(
                key: $_ENV['PRIVATE_AUDIO_KEY'],
                secret: $_ENV['PRIVATE_AUDIO_SECRET'],
                endpoint: $_ENV['PRIVATE_AUDIO_ENDPOINT'],
                bucket: $_ENV['PRIVATE_AUDIO_BUCKET'],
                region: $_ENV['PRIVATE_AUDIO_REGION'],
                cdnUrl: null,  // ❌ No CDN for private access
                acl: 'private'
            ),
        );
    }
}
