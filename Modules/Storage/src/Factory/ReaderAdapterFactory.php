<?php

declare(strict_types=1);

namespace Maatify\Storage\Factory;

use Aws\S3\S3Client;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\SharedCommon\Path\AppPaths;
use Maatify\Storage\Adapters\DOSpacesStorageReader;
use Maatify\Storage\Adapters\LocalStorageReader;
use Maatify\Storage\Contracts\ReaderAdapterInterface;
use Maatify\Storage\Contracts\StorageConfigInterface;
use Maatify\Storage\Exception\ConfigurationException;

/**
 * Factory for creating reader adapters.
 *
 * Works with any StorageConfigInterface implementation to create
 * appropriate reader adapters (local or DigitalOcean Spaces).
 *
 * Mirrors StorageAdapterFactory pattern: accepts both AppPaths and config.
 * Returns ReaderAdapterInterface for reading files.
 *
 * @see StorageAdapterFactory
 * @see StorageConfigInterface
 */
final class ReaderAdapterFactory
{
    public static function create(AppPaths $paths, StorageConfigInterface $config, ClockInterface $clock): ReaderAdapterInterface
    {
        return match ($config->getDriver()) {
            'local'     => self::createLocalReader($paths, $config, $clock),
            'do_spaces' => self::createDOSpacesReader($config, $clock),
            default     => throw ConfigurationException::unsupportedDriver($config->getDriver()),
        };
    }

    private static function createLocalReader(AppPaths $paths, StorageConfigInterface $config, ClockInterface $clock): LocalStorageReader
    {
        $local = $config->getLocalConfig();

        return new LocalStorageReader(
            storagePath: $local?->basePath ?? $paths->publicImages(),
            clock: $clock,
        );
    }

    private static function createDOSpacesReader(StorageConfigInterface $config, ClockInterface $clock): DOSpacesStorageReader
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

        return new DOSpacesStorageReader(
            client: $client,
            bucket: $spaces->bucket,
            clock: $clock,
        );
    }
}
