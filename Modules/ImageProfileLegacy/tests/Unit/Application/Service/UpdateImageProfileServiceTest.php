<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace ImageProfileLegacy\tests\Unit\Application\Service;

use ImageProfileLegacy\Application\Contract\ImageProfileRepositoryInterface;
use ImageProfileLegacy\Application\DTO\UpdateImageProfileRequest;
use ImageProfileLegacy\Application\Service\UpdateImageProfileService;
use ImageProfileLegacy\tests\Fixtures\ImageProfileFixtureFactory;
use Maatify\ImageProfileLegacy\Exception\ImageProfileNotFoundException;
use Maatify\ImageProfileLegacy\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfileLegacy\ValueObject\AllowedMimeTypeCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\ImageProfileLegacy\Application\Service\UpdateImageProfileService::class)]
final class UpdateImageProfileServiceTest extends TestCase
{
    private ImageProfileRepositoryInterface&MockObject $repository;
    private UpdateImageProfileService                  $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ImageProfileRepositoryInterface::class);
        $this->service    = new UpdateImageProfileService($this->repository);
    }

    private function makeRequest(): UpdateImageProfileRequest
    {
        return new UpdateImageProfileRequest(
            displayName:       'Updated Profile',
            minWidth:          200,
            minHeight:         200,
            maxWidth:          3000,
            maxHeight:         3000,
            maxSizeBytes:      5_242_880,
            allowedExtensions: new AllowedExtensionCollection('jpg', 'webp'),
            allowedMimeTypes:  new AllowedMimeTypeCollection('image/jpeg', 'image/webp'),
            notes:             'Updated via admin panel',
        );
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    public function test_delegates_to_repository_update(): void
    {
        $expected = ImageProfileFixtureFactory::standard();
        $request  = $this->makeRequest();

        $this->repository
            ->expects(self::once())
            ->method('update')
            ->with('standard_profile', $request)
            ->willReturn($expected);

        $result = $this->service->execute('standard_profile', $request);

        self::assertSame($expected, $result);
    }

    public function test_returns_updated_entity(): void
    {
        $updated = ImageProfileFixtureFactory::standard();
        $this->repository->method('update')->willReturn($updated);

        $result = $this->service->execute('standard_profile', $this->makeRequest());

        self::assertSame($updated, $result);
    }

    // -------------------------------------------------------------------------
    // Not found propagation
    // -------------------------------------------------------------------------

    public function test_propagates_not_found_exception_from_repository(): void
    {
        $this->expectException(ImageProfileNotFoundException::class);

        $this->repository
            ->method('update')
            ->willThrowException(ImageProfileNotFoundException::forCode('ghost_profile'));

        $this->service->execute('ghost_profile', $this->makeRequest());
    }
}
