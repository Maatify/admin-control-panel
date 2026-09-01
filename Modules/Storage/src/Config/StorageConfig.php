<?php

declare(strict_types=1);

namespace Maatify\Storage\Config;

use Maatify\Storage\Contracts\StorageConfigInterface;
use Maatify\Storage\Exception\ConfigurationException;

/**
 * Default storage configuration.
 *
 * Reads from environment variables and provides single-driver setup.
 * For multi-driver setups, projects should implement StorageConfigInterface.
 *
 * @see StorageConfigInterface for extending with custom configurations
 */
final readonly class StorageConfig implements StorageConfigInterface
{
    public function __construct(
        public string                  $driver,
        public LocalStorageConfig|null $local = null,
        public DOSpacesConfig|null     $doSpaces = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalConfig(): ?LocalStorageConfig
    {
        return $this->local;
    }

    /**
     * {@inheritDoc}
     */
    public function getDoSpacesConfig(): ?DOSpacesConfig
    {
        return $this->doSpaces;
    }

    /**
     * Build from raw $_ENV or any flat array
     *
     * @param array<string, string> $env
     */
    public static function fromEnv(array $env): self
    {
        $driver = $env['STORAGE_DRIVER'] ?? 'local';

        return new self(
            driver: $driver,
            local: !empty($env['LOCAL_BASE_PATH'])
                ? new LocalStorageConfig(
                    basePath: $env['LOCAL_BASE_PATH'],
                    baseUrl:  $env['LOCAL_BASE_URL'] ?? '/images',
                )
                : null,
            doSpaces: $driver === 'do_spaces'
                ? new DOSpacesConfig(
                    key:      $env['DO_SPACES_KEY']      ?? throw ConfigurationException::missingEnvVariable('DO_SPACES_KEY'),
                    secret:   $env['DO_SPACES_SECRET']   ?? throw ConfigurationException::missingEnvVariable('DO_SPACES_SECRET'),
                    endpoint: $env['DO_SPACES_ENDPOINT'] ?? throw ConfigurationException::missingEnvVariable('DO_SPACES_ENDPOINT'),
                    bucket:   $env['DO_SPACES_BUCKET']   ?? throw ConfigurationException::missingEnvVariable('DO_SPACES_BUCKET'),
                    region:   $env['DO_SPACES_REGION']   ?? throw ConfigurationException::missingEnvVariable('DO_SPACES_REGION'),
                    cdnUrl:   $env['DO_SPACES_CDN_URL']  ?? throw ConfigurationException::missingEnvVariable('DO_SPACES_CDN_URL'),
                    acl:      $env['DO_SPACES_ACL']      ?? 'public-read',
                )
                : null,
        );
    }
}
