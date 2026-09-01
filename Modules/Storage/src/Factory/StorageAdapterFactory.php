<?php

declare(strict_types=1);

namespace Maatify\Storage\Factory;

use Aws\S3\S3Client;
use Maatify\SharedCommon\Path\AppPaths;
use Maatify\Storage\Adapters\DOSpacesStorageAdapter;
use Maatify\Storage\Adapters\LocalStorageAdapter;
use Maatify\Storage\Contracts\StorageAdapterInterface;
use Maatify\Storage\Contracts\StorageConfigInterface;
use Maatify\Storage\Exception\ConfigurationException;

/**
 * Factory for creating storage adapters.
 *
 * Works with any StorageConfigInterface implementation,
 * allowing projects to use custom configurations.
 *
 * @see StorageConfigInterface
 */
final class StorageAdapterFactory
{
    public static function create(AppPaths $paths, StorageConfigInterface $config): StorageAdapterInterface
    {
        return match ($config->getDriver()) {
            'local'     => self::createLocalAdapter($paths, $config),
            'do_spaces' => self::createDOSpacesAdapter($config),
            default     => throw ConfigurationException::unsupportedDriver($config->getDriver()),
        };
    }

    private static function createLocalAdapter(AppPaths $paths, StorageConfigInterface $config): LocalStorageAdapter
    {
        $local = $config->getLocalConfig();

        return new LocalStorageAdapter(
            basePath: $local?->basePath ?? $paths->publicPath(),
            baseUrl:  $local?->baseUrl  ?? '',
        );
    }

    private static function createDOSpacesAdapter(StorageConfigInterface $config): DOSpacesStorageAdapter
    {
        $spaces = $config->getDoSpacesConfig()
                  ?? throw ConfigurationException::missingAdapterConfig('do_spaces');

        $client = new S3Client([
            'version'                 => 'latest',
            'region'                  => $spaces->region,
            'endpoint'                => $spaces->endpoint,
            'credentials'             => [
                'key'    => $spaces->key,
                'secret' => $spaces->secret,
            ],
            'use_path_style_endpoint' => false,
        ]);

        return new DOSpacesStorageAdapter(
            client: $client,
            bucket: $spaces->bucket,
            cdnUrl: $spaces->cdnUrl,
            acl:    $spaces->acl,
        );
    }
}
