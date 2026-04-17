<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests;

use Maatify\ImageProfile\Command\CreateImageProfileCommand;
use Maatify\ImageProfile\Command\UpdateImageProfileStatusCommand;
use Maatify\ImageProfile\Contract\ImageProfileCommandRepositoryInterface;
use Maatify\ImageProfile\Contract\ImageProfileQueryReaderInterface;
use Maatify\ImageProfile\DTO\ImageProfileDTO;
use Maatify\ImageProfile\DTO\ImageProfilePaginatedResultDTO;
use Maatify\ImageProfile\DTO\PaginationDTO;
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
}
