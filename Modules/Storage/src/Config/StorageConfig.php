<?php

declare(strict_types=1);

namespace Maatify\Storage\Config;

use Maatify\Storage\Exceptions\ConfigurationException;

final class StorageConfig
{
    public function __construct(
        public readonly string $driver,
        public readonly LocalStorageConfig|null $local = null,
        public readonly DOSpacesConfig|null $doSpaces = null,
    ) {}

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
            local: isset($env['LOCAL_BASE_PATH'])
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
