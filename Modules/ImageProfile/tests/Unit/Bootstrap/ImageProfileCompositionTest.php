<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Unit\Bootstrap;

use Maatify\ImageProfile\Bootstrap\ImageProfileComposition;
use Maatify\ImageProfile\Provider\ArrayImageProfileProvider;
use Maatify\ImageProfile\Reader\NativeImageMetadataReader;
use Maatify\ImageProfile\Service\ImageProfileValidationService;
use Maatify\ImageProfile\Tests\Fixtures\ImageProfileFixtureFactory;
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
