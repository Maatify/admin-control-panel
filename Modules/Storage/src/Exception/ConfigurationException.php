<?php

declare(strict_types=1);

namespace Maatify\Storage\Exception;

/**
 * Thrown when storage module configuration is invalid or incomplete.
 *
 * Per Maatify Module Building Standard section 6 - named constructors required.
 */
final class ConfigurationException extends StorageException
{
    public static function missingEnvVariable(string $key): self
    {
        return new self("Missing required environment variable: {$key}");
    }

    public static function unsupportedDriver(string $driver): self
    {
        return new self("Unsupported storage driver: [{$driver}]");
    }

    public static function missingAdapterConfig(string $driver): self
    {
        return new self(
            sprintf('Config for driver "%s" is required but was not provided.', $driver)
        );
    }

    public static function failedToResolveProjectRoot(): self
    {
        return new self('Failed to resolve project root directory.');
    }
}
