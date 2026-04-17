<?php

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Unit\Bootstrap;

use ImageProfileLegacy\tests\Fixtures\ImageProfileFixtureFactory;
use Maatify\ImageProfileLegacy\Bootstrap\ImageProfileComposition;
use Maatify\ImageProfileLegacy\Provider\ArrayImageProfileProvider;
use Maatify\ImageProfileLegacy\Reader\NativeImageMetadataReader;
use Maatify\ImageProfileLegacy\Service\ImageProfileValidationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageProfileComposition::class)]
final class ImageProfileCompositionTest extends TestCase
{
    public function testFromProviderBuildsValidationService(): void
    {
        $provider = new ArrayImageProfileProvider(ImageProfileFixtureFactory::standard());
        $reader = new NativeImageMetadataReader();

        $service = ImageProfileComposition::fromProvider($provider, $reader);

        self::assertInstanceOf(ImageProfileValidationService::class, $service);
        self::assertCount(1, $service->listAllProfiles());
    }

    public function testFromPdoBuildsValidationService(): void
    {
        $pdo = new \PDO('sqlite::memory:');

        $service = ImageProfileComposition::fromPdo($pdo);

        self::assertInstanceOf(ImageProfileValidationService::class, $service);
    }
}
