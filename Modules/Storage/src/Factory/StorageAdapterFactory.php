<?php

declare(strict_types=1);

namespace Maatify\Storage\Factory;

use Aws\S3\S3Client;
use Maatify\SharedCommon\Path\AppPaths;
use Maatify\Storage\Adapters\DOSpacesStorageAdapter;
use Maatify\Storage\Adapters\LocalStorageAdapter;
use Maatify\Storage\Config\StorageConfig;
use Maatify\Storage\Contracts\StorageAdapterInterface;
use Maatify\Storage\Exceptions\ConfigurationException;

final class StorageAdapterFactory
{
    public static function create(AppPaths $paths, StorageConfig $config): StorageAdapterInterface
    {
        return match ($config->driver) {
            'local'     => self::createLocalAdapter($paths, $config),
            'do_spaces' => self::createDOSpacesAdapter($config),
            default     => throw ConfigurationException::unsupportedDriver($config->driver),
        };
    }

    private static function createLocalAdapter(AppPaths $paths, StorageConfig $config): LocalStorageAdapter
    {
        $local = $config->local;

        return new LocalStorageAdapter(
            basePath: $local?->basePath ?? $paths->publicImages(),
            baseUrl:  $local?->baseUrl  ?? '/images',
        );
    }

    private static function createDOSpacesAdapter(StorageConfig $config): DOSpacesStorageAdapter
    {
        $spaces = $config->doSpaces
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
