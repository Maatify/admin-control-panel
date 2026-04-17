<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests\Unit\Application\Service;

use Maatify\ImageProfile\Application\Contract\ImageProfileRepositoryInterface;
use Maatify\ImageProfile\Application\DTO\CreateImageProfileRequest;
use Maatify\ImageProfile\Application\Exception\DuplicateProfileCodeException;
use Maatify\ImageProfile\Application\Service\CreateImageProfileService;
use Maatify\ImageProfile\Tests\Fixtures\ImageProfileFixtureFactory;
use Maatify\ImageProfile\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Maatify\ImageProfile\Application\Service\CreateImageProfileService::class)]
final class CreateImageProfileServiceTest extends TestCase
{
    private ImageProfileRepositoryInterface&MockObject $repository;
    private CreateImageProfileService                  $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ImageProfileRepositoryInterface::class);
        $this->service    = new CreateImageProfileService($this->repository);
    }

    private function makeRequest(string $code = 'new_profile'): CreateImageProfileRequest
    {
        return new CreateImageProfileRequest(
            code:              $code,
            displayName:       'New Profile',
            minWidth:          100,
            minHeight:         100,
            maxWidth:          2000,
            maxHeight:         2000,
            maxSizeBytes:      2_097_152,
            allowedExtensions: new AllowedExtensionCollection('jpg', 'png', 'webp'),
            allowedMimeTypes:  new AllowedMimeTypeCollection('image/jpeg', 'image/png', 'image/webp'),
            isActive:          true,
        );
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    public function test_creates_profile_when_code_is_unique(): void
    {
        $expected = ImageProfileFixtureFactory::standard();
        $request  = $this->makeRequest('standard_profile');

        $this->repository
            ->method('existsByCode')
            ->with('standard_profile')
            ->willReturn(false);

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with($request)
            ->willReturn($expected);

        $result = $this->service->execute($request);

        self::assertSame($expected, $result);
    }

    public function test_calls_repository_save_exactly_once(): void
    {
        $request = $this->makeRequest();

        $this->repository->method('existsByCode')->willReturn(false);
        $this->repository
            ->expects(self::once())
            ->method('save')
            ->willReturn(ImageProfileFixtureFactory::standard());

        $this->service->execute($request);
    }

    // -------------------------------------------------------------------------
    // Duplicate code guard
    // -------------------------------------------------------------------------

    public function test_throws_duplicate_exception_when_code_already_exists(): void
    {
        $this->expectException(DuplicateProfileCodeException::class);

        $this->repository
            ->method('existsByCode')
            ->willReturn(true);

        $this->repository
            ->expects(self::never())
            ->method('save');

        $this->service->execute($this->makeRequest('existing_code'));
    }

    public function test_exception_message_contains_code(): void
    {
        $this->repository->method('existsByCode')->willReturn(true);

        try {
            $this->service->execute($this->makeRequest('my_profile'));
            self::fail('Expected DuplicateProfileCodeException was not thrown');
        } catch (DuplicateProfileCodeException $e) {
            self::assertStringContainsString('my_profile', $e->getMessage());
        }
    }

    public function test_save_is_not_called_when_code_is_duplicate(): void
    {
        $this->repository->method('existsByCode')->willReturn(true);
        $this->repository->expects(self::never())->method('save');

        try {
            $this->service->execute($this->makeRequest());
        } catch (DuplicateProfileCodeException) {
            // expected
        }
    }
}
