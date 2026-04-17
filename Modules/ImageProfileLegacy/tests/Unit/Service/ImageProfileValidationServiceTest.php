<?php

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Unit\Service;

use ImageProfileLegacy\tests\Fixtures\ImageProfileFixtureFactory;
use Maatify\ImageProfileLegacy\Contract\ImageProfileProviderInterface;
use Maatify\ImageProfileLegacy\Contract\ImageProfileValidatorInterface;
use Maatify\ImageProfileLegacy\DTO\ImageFileInputDTO;
use Maatify\ImageProfileLegacy\DTO\ImageProfileCollectionDTO;
use Maatify\ImageProfileLegacy\DTO\ImageValidationErrorCollectionDTO;
use Maatify\ImageProfileLegacy\DTO\ImageValidationErrorDTO;
use Maatify\ImageProfileLegacy\DTO\ImageValidationResultDTO;
use Maatify\ImageProfileLegacy\Service\ImageProfileValidationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageProfileValidationService::class)]
final class ImageProfileValidationServiceTest extends TestCase
{
    public function testItDelegatesProfileLookupAndValidationCalls(): void
    {
        $profile = ImageProfileFixtureFactory::standard();
        $input = new ImageFileInputDTO(
            originalName: 'file.jpg',
            temporaryPath: '/tmp/file.jpg',
            clientMimeType: 'image/jpeg',
            sizeBytes: 123,
        );

        $expectedResult = ImageValidationResultDTO::invalid(
            profileCode: $profile->code,
            metadata: null,
            errors: new ImageValidationErrorCollectionDTO(
                ImageValidationErrorDTO::profileNotFound($profile->code),
            ),
        );

        $provider = $this->createMock(ImageProfileProviderInterface::class);
        $provider->expects($this->once())
            ->method('findByCode')
            ->with($profile->code)
            ->willReturn($profile);

        $provider->expects($this->once())
            ->method('listAll')
            ->willReturn(new ImageProfileCollectionDTO($profile));

        $provider->expects($this->once())
            ->method('listActive')
            ->willReturn(new ImageProfileCollectionDTO($profile));

        $validator = $this->createMock(ImageProfileValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validateByCode')
            ->with($profile->code, $input)
            ->willReturn($expectedResult);

        $service = new ImageProfileValidationService($provider, $validator);

        self::assertSame($profile, $service->findProfileByCode($profile->code));
        self::assertCount(1, $service->listAllProfiles());
        self::assertCount(1, $service->listActiveProfiles());
        self::assertSame($expectedResult, $service->validateByCode($profile->code, $input));
    }
}
