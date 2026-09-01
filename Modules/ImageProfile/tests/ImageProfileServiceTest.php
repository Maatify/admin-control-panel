<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests;

use Maatify\ImageProfile\Command\CreateImageProfileCommand;
use Maatify\ImageProfile\Command\UpdateImageProfileCommand;
use Maatify\ImageProfile\Command\UpdateImageProfileStatusCommand;
use Maatify\ImageProfile\Contract\ImageProfileCommandRepositoryInterface;
use Maatify\ImageProfile\Contract\ImageProfileQueryReaderInterface;
use Maatify\ImageProfile\DTO\ImageProfileDTO;
use Maatify\ImageProfile\DTO\ImageProfilePaginatedResultDTO;
use Maatify\ImageProfile\DTO\PaginationDTO;
use Maatify\ImageProfile\Exception\ImageProfileCodeAlreadyExistsException;
use Maatify\ImageProfile\Exception\ImageProfileNotFoundException;
use Maatify\ImageProfile\Service\ImageProfileCommandService;
use PHPUnit\Framework\TestCase;

final class ImageProfileServiceTest extends TestCase
{
    public function testCreateDelegatesToRepositoryAfterUniquenessCheck(): void
    {
        $existing = null;

        $query = new class($existing) implements ImageProfileQueryReaderInterface {
            public function __construct(private readonly ?ImageProfileDTO $existing) {}
            public function listProfiles(int $page, int $perPage, ?string $globalSearch, array $columnFilters): ImageProfilePaginatedResultDTO { return new ImageProfilePaginatedResultDTO([], new PaginationDTO(1, 20, 0, 0)); }
            public function listActiveProfiles(): array { return []; }
            public function findById(int $id): ?ImageProfileDTO { return null; }
            public function findByCode(string $code): ?ImageProfileDTO { return $this->existing; }
        };

        $repo = new class implements ImageProfileCommandRepositoryInterface {
            public function create(CreateImageProfileCommand $command): ImageProfileDTO { return new ImageProfileDTO(1, $command->code, null, null, null, null, null, null, null, null, true, null, null, null, false, null, null, null, '2026-01-01 00:00:00', null); }
            public function update(\Maatify\ImageProfile\Command\UpdateImageProfileCommand $command): ImageProfileDTO { throw new \RuntimeException('not used'); }
            public function updateStatus(UpdateImageProfileStatusCommand $command): ImageProfileDTO { throw new \RuntimeException('not used'); }
        };

        $service = new ImageProfileCommandService($repo, $query);
        $created = $service->create(new CreateImageProfileCommand('avatar'));

        self::assertSame('avatar', $created->code);
    }

    public function testCreateRejectsAnExistingCodeBeforeWriting(): void
    {
        $existing = $this->profile(7, 'avatar');
        $query = $this->createMock(ImageProfileQueryReaderInterface::class);
        $query->expects(self::once())
            ->method('findByCode')
            ->with('avatar')
            ->willReturn($existing);
        $query->expects(self::never())->method('findById');

        $repo = $this->createMock(ImageProfileCommandRepositoryInterface::class);
        $repo->expects(self::never())->method('create');

        $this->expectException(ImageProfileCodeAlreadyExistsException::class);
        (new ImageProfileCommandService($repo, $query))->create(new CreateImageProfileCommand('avatar'));
    }

    public function testUpdateAllowsTheExistingCodeForTheSameProfile(): void
    {
        $existing = $this->profile(7, 'avatar');
        $command = new UpdateImageProfileCommand(7, 'avatar', displayName: 'Updated avatar');
        $query = $this->createMock(ImageProfileQueryReaderInterface::class);
        $query->expects(self::once())->method('findById')->with(7)->willReturn($existing);
        $query->expects(self::once())->method('findByCode')->with('avatar')->willReturn($existing);

        $repo = $this->createMock(ImageProfileCommandRepositoryInterface::class);
        $repo->expects(self::once())->method('update')->with($command)->willReturn($existing);

        self::assertSame($existing, (new ImageProfileCommandService($repo, $query))->update($command));
    }

    public function testUpdateRejectsAnUnknownProfile(): void
    {
        $query = $this->createMock(ImageProfileQueryReaderInterface::class);
        $query->expects(self::once())->method('findById')->with(99)->willReturn(null);
        $query->expects(self::never())->method('findByCode');

        $repo = $this->createMock(ImageProfileCommandRepositoryInterface::class);
        $repo->expects(self::never())->method('update');

        $this->expectException(ImageProfileNotFoundException::class);
        (new ImageProfileCommandService($repo, $query))->update(new UpdateImageProfileCommand(99, 'missing'));
    }

    public function testUpdateRejectsACodeOwnedByAnotherProfile(): void
    {
        $target = $this->profile(7, 'avatar');
        $other = $this->profile(8, 'cover');
        $command = new UpdateImageProfileCommand(7, 'cover');
        $query = $this->createMock(ImageProfileQueryReaderInterface::class);
        $query->expects(self::once())->method('findById')->with(7)->willReturn($target);
        $query->expects(self::once())->method('findByCode')->with('cover')->willReturn($other);

        $repo = $this->createMock(ImageProfileCommandRepositoryInterface::class);
        $repo->expects(self::never())->method('update');

        $this->expectException(ImageProfileCodeAlreadyExistsException::class);
        (new ImageProfileCommandService($repo, $query))->update($command);
    }

    public function testUpdateStatusChecksExistenceThenDelegates(): void
    {
        $existing = $this->profile(7, 'avatar');
        $command = new UpdateImageProfileStatusCommand(7, false);
        $query = $this->createMock(ImageProfileQueryReaderInterface::class);
        $query->expects(self::once())->method('findById')->with(7)->willReturn($existing);

        $repo = $this->createMock(ImageProfileCommandRepositoryInterface::class);
        $repo->expects(self::once())->method('updateStatus')->with($command)->willReturn($existing);

        self::assertSame($existing, (new ImageProfileCommandService($repo, $query))->updateStatus($command));
    }

    public function testUpdateStatusRejectsAnUnknownProfile(): void
    {
        $query = $this->createMock(ImageProfileQueryReaderInterface::class);
        $query->expects(self::once())->method('findById')->with(99)->willReturn(null);

        $repo = $this->createMock(ImageProfileCommandRepositoryInterface::class);
        $repo->expects(self::never())->method('updateStatus');

        $this->expectException(ImageProfileNotFoundException::class);
        (new ImageProfileCommandService($repo, $query))->updateStatus(new UpdateImageProfileStatusCommand(99, true));
    }

    private function profile(int $id, string $code): ImageProfileDTO
    {
        return new ImageProfileDTO(
            id: $id,
            code: $code,
            displayName: null,
            minWidth: null,
            minHeight: null,
            maxWidth: null,
            maxHeight: null,
            maxSizeBytes: null,
            allowedExtensions: null,
            allowedMimeTypes: null,
            isActive: true,
            notes: null,
            minAspectRatio: null,
            maxAspectRatio: null,
            requiresTransparency: false,
            preferredFormat: null,
            preferredQuality: null,
            variants: null,
            createdAt: '2026-01-01 00:00:00',
            updatedAt: null,
        );
    }
}
