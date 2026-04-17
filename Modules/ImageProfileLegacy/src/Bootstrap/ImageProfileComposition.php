<?php

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\Bootstrap;

use Maatify\ImageProfileLegacy\Contract\ImageMetadataReaderInterface;
use Maatify\ImageProfileLegacy\Contract\ImageProfileProviderInterface;
use Maatify\ImageProfileLegacy\Contract\ImageProfileValidationServiceInterface;
use Maatify\ImageProfileLegacy\Infrastructure\Persistence\PDO\PdoImageProfileProvider;
use Maatify\ImageProfileLegacy\Reader\NativeImageMetadataReader;
use Maatify\ImageProfileLegacy\Service\ImageProfileValidationService;
use PDO;

/**
 * Framework-agnostic composition helper for the ImageProfile library.
 *
 * This class intentionally avoids any dependency on a specific DI container.
 * It provides common wiring paths while allowing host projects to build their
 * own container bindings if desired.
 */
final class ImageProfileComposition
{
    /**
     * Compose the public validation service from explicit dependencies.
     */
    public static function fromProvider(
        ImageProfileProviderInterface $provider,
        ImageMetadataReaderInterface $reader,
    ): ImageProfileValidationServiceInterface {
        return ImageProfileValidationService::compose($provider, $reader);
    }

    /**
     * Compose the public validation service from a PDO-backed profile provider.
     *
     * This is a convenience for consumers that want the ready-to-use PDO path
     * without introducing a framework-specific binding class.
     */
    public static function fromPdo(
        PDO $pdo,
        string $table = 'image_profiles',
        ?ImageMetadataReaderInterface $reader = null,
    ): ImageProfileValidationServiceInterface {
        return self::fromProvider(
            provider: new PdoImageProfileProvider($pdo, $table),
            reader: $reader ?? new NativeImageMetadataReader(),
        );
    }
}
