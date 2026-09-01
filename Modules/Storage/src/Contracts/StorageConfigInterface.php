<?php

declare(strict_types=1);

namespace Maatify\Storage\Contracts;

use Maatify\Storage\Config\DOSpacesConfig;
use Maatify\Storage\Config\LocalStorageConfig;

/**
 * Interface for storage configuration.
 *
 * Allows projects to implement custom storage configurations
 * for different scenarios (public, private, media, backups, etc.)
 *
 * @example
 * class PublicStorageConfig implements StorageConfigInterface {
 *     public function getDriver(): string { return 'do_spaces'; }
 *     public function getLocalConfig(): ?LocalStorageConfig { return null; }
 *     public function getDoSpacesConfig(): ?DOSpacesConfig { return ...; }
 * }
 */
interface StorageConfigInterface
{
    /**
     * Get the storage driver: 'local' or 'do_spaces'
     */
    public function getDriver(): string;

    /**
     * Get local storage configuration (if using local driver)
     */
    public function getLocalConfig(): ?LocalStorageConfig;

    /**
     * Get DigitalOcean Spaces configuration (if using do_spaces driver)
     */
    public function getDoSpacesConfig(): ?DOSpacesConfig;
}
