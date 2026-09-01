<?php

declare(strict_types=1);

namespace Maatify\Storage\Tests\Unit\Config;

use Maatify\Storage\Config\DOSpacesConfig;
use Maatify\Storage\Config\LocalStorageConfig;
use Maatify\Storage\Config\StorageConfig;
use Maatify\Storage\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for StorageConfig
 */
final class StorageConfigTest extends TestCase
{
    public function testCreatesLocalStorageConfig(): void
    {
        $config = new StorageConfig(
            driver: 'local',
            local: new LocalStorageConfig(
                basePath: '/storage',
                baseUrl: '/uploads'
            )
        );

        $this->assertEquals('local', $config->driver);
        $this->assertNotNull($config->local);
        $this->assertEquals('/storage', $config->local->basePath);
        $this->assertEquals('/uploads', $config->local->baseUrl);
        $this->assertNull($config->doSpaces);
    }

    public function testCreatesDigitalOceanSpacesConfig(): void
    {
        $config = new StorageConfig(
            driver: 'do_spaces',
            doSpaces: new DOSpacesConfig(
                key: 'test-key',
                secret: 'test-secret',
                endpoint: 'https://fra1.digitaloceanspaces.com',
                bucket: 'my-bucket',
                region: 'fra1',
                cdnUrl: 'https://my-bucket.fra1.cdn.digitaloceanspaces.com'
            )
        );

        $this->assertEquals('do_spaces', $config->driver);
        $this->assertNull($config->local);
        $this->assertNotNull($config->doSpaces);
        $this->assertEquals('test-key', $config->doSpaces->key);
        $this->assertEquals('my-bucket', $config->doSpaces->bucket);
    }

    public function testFromEnvWithLocalDriver(): void
    {
        $env = [
            'STORAGE_DRIVER' => 'local',
            'LOCAL_BASE_PATH' => '/var/storage',
            'LOCAL_BASE_URL' => '/files',
        ];

        $config = StorageConfig::fromEnv($env);

        $this->assertEquals('local', $config->driver);
        $this->assertNotNull($config->local);
        $this->assertEquals('/var/storage', $config->local->basePath);
    }

    public function testFromEnvDefaultsToLocal(): void
    {
        $env = [];

        $config = StorageConfig::fromEnv($env);

        $this->assertEquals('local', $config->driver);
    }

    public function testFromEnvWithDigitalOceanSpaces(): void
    {
        $env = [
            'STORAGE_DRIVER' => 'do_spaces',
            'DO_SPACES_KEY' => 'my-key',
            'DO_SPACES_SECRET' => 'my-secret',
            'DO_SPACES_ENDPOINT' => 'https://nyc3.digitaloceanspaces.com',
            'DO_SPACES_BUCKET' => 'my-files',
            'DO_SPACES_REGION' => 'nyc3',
            'DO_SPACES_CDN_URL' => 'https://my-files.nyc3.cdn.digitaloceanspaces.com',
        ];

        $config = StorageConfig::fromEnv($env);

        $this->assertEquals('do_spaces', $config->driver);
        $this->assertNotNull($config->doSpaces);
        $this->assertEquals('my-key', $config->doSpaces->key);
        $this->assertEquals('my-files', $config->doSpaces->bucket);
    }

    public function testFromEnvThrowsOnMissingDoSpacesKey(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('DO_SPACES_KEY');

        $env = [
            'STORAGE_DRIVER' => 'do_spaces',
            // Missing DO_SPACES_KEY
            'DO_SPACES_SECRET' => 'secret',
            'DO_SPACES_ENDPOINT' => 'endpoint',
            'DO_SPACES_BUCKET' => 'bucket',
            'DO_SPACES_REGION' => 'region',
            'DO_SPACES_CDN_URL' => 'url',
        ];

        StorageConfig::fromEnv($env);
    }

    public function testFromEnvThrowsOnMissingDoSpacesBucket(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('DO_SPACES_BUCKET');

        $env = [
            'STORAGE_DRIVER' => 'do_spaces',
            'DO_SPACES_KEY' => 'key',
            'DO_SPACES_SECRET' => 'secret',
            'DO_SPACES_ENDPOINT' => 'endpoint',
            // Missing DO_SPACES_BUCKET
            'DO_SPACES_REGION' => 'region',
            'DO_SPACES_CDN_URL' => 'url',
        ];

        StorageConfig::fromEnv($env);
    }

    public function testFromEnvUsesDefaultAclForSpaces(): void
    {
        $env = [
            'STORAGE_DRIVER' => 'do_spaces',
            'DO_SPACES_KEY' => 'key',
            'DO_SPACES_SECRET' => 'secret',
            'DO_SPACES_ENDPOINT' => 'endpoint',
            'DO_SPACES_BUCKET' => 'bucket',
            'DO_SPACES_REGION' => 'region',
            'DO_SPACES_CDN_URL' => 'url',
            // No DO_SPACES_ACL
        ];

        $config = StorageConfig::fromEnv($env);

        $this->assertNotNull($config->doSpaces);
        $this->assertEquals('public-read', $config->doSpaces->acl);
    }

    public function testIsReadonly(): void
    {
        $config = new StorageConfig('local');

        // Should be readonly
        $this->expectException(\Error::class);
        $config->driver = 'do_spaces'; // @phpstan-ignore-line
    }
}
