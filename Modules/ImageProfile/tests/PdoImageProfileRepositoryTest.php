<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests;

use Maatify\ImageProfile\Command\CreateImageProfileCommand;
use Maatify\ImageProfile\Command\UpdateImageProfileCommand;
use Maatify\ImageProfile\Command\UpdateImageProfileStatusCommand;
use Maatify\ImageProfile\DTO\ImageProfilePaginatedResultDTO;
use Maatify\ImageProfile\Exception\ImageProfileCodeAlreadyExistsException;
use Maatify\ImageProfile\Infrastructure\Repository\PdoImageProfileCommandRepository;
use Maatify\ImageProfile\Infrastructure\Repository\PdoImageProfileQueryReader;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoImageProfileRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE maa_image_profiles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code VARCHAR(64) NOT NULL UNIQUE,
            display_name VARCHAR(128) NULL,
            min_width INTEGER NULL,
            min_height INTEGER NULL,
            max_width INTEGER NULL,
            max_height INTEGER NULL,
            max_size_bytes INTEGER NULL,
            allowed_extensions VARCHAR(255) NULL,
            allowed_mime_types TEXT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            notes TEXT NULL,
            min_aspect_ratio DECIMAL(8,4) NULL,
            max_aspect_ratio DECIMAL(8,4) NULL,
            requires_transparency INTEGER NOT NULL DEFAULT 0,
            preferred_format VARCHAR(10) NULL,
            preferred_quality INTEGER NULL,
            variants TEXT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    public function testCreateAndFindByCode(): void
    {
        $commandRepo = new PdoImageProfileCommandRepository($this->pdo);
        $queryReader = new PdoImageProfileQueryReader($this->pdo);

        $created = $commandRepo->create(new CreateImageProfileCommand(
            code: 'product_thumb',
            displayName: 'Product thumb',
            minWidth: 100,
            minHeight: 100,
            maxWidth: 1000,
            maxHeight: 1000,
            allowedExtensions: 'jpg,png',
            allowedMimeTypes: 'image/jpeg,image/png',
            maxSizeBytes: 1048576,
            isActive: true,
            minAspectRatio: '1.0000',
            maxAspectRatio: '1.0000',
        ));

        self::assertSame('product_thumb', $created->code);

        $found = $queryReader->findByCode('product_thumb');
        self::assertNotNull($found);
        self::assertSame($created->id, $found->id);
        self::assertSame(100, $found->minWidth);
    }

    public function testUpdateStatusAndListActive(): void
    {
        $commandRepo = new PdoImageProfileCommandRepository($this->pdo);
        $queryReader = new PdoImageProfileQueryReader($this->pdo);

        $profile = $commandRepo->create(new CreateImageProfileCommand(code: 'banner'));
        $commandRepo->updateStatus(new UpdateImageProfileStatusCommand($profile->id, false));

        $active = $queryReader->listActiveProfiles();
        self::assertCount(0, $active);

        $commandRepo->updateStatus(new UpdateImageProfileStatusCommand($profile->id, true));
        $active = $queryReader->listActiveProfiles();
        self::assertCount(1, $active);
        self::assertSame('banner', $active[0]->code);
    }

    public function testListProfilesReturnsPaginatedDto(): void
    {
        $commandRepo = new PdoImageProfileCommandRepository($this->pdo);
        $queryReader = new PdoImageProfileQueryReader($this->pdo);

        $commandRepo->create(new CreateImageProfileCommand(code: 'first'));
        $commandRepo->create(new CreateImageProfileCommand(code: 'second'));

        $result = $queryReader->listProfiles(1, 1, null, []);

        self::assertInstanceOf(ImageProfilePaginatedResultDTO::class, $result);
        self::assertCount(1, $result->data);
        self::assertSame(2, $result->pagination->total);
        self::assertSame(2, $result->pagination->filtered);
    }

    public function testDuplicateCodeThrowsDedicatedException(): void
    {
        $commandRepo = new PdoImageProfileCommandRepository($this->pdo);
        $commandRepo->create(new CreateImageProfileCommand(code: 'hero'));

        $this->expectException(ImageProfileCodeAlreadyExistsException::class);
        $commandRepo->create(new CreateImageProfileCommand(code: 'hero'));
    }

    public function testUpdatePersistsEveryMutableProfileField(): void
    {
        $commandRepo = new PdoImageProfileCommandRepository($this->pdo);
        $queryReader = new PdoImageProfileQueryReader($this->pdo);
        $created = $commandRepo->create(new CreateImageProfileCommand(code: 'original'));

        $updated = $commandRepo->update(new UpdateImageProfileCommand(
            id: $created->id,
            code: 'updated',
            displayName: 'Updated profile',
            minWidth: 120,
            minHeight: 130,
            maxWidth: 1200,
            maxHeight: 1300,
            maxSizeBytes: 2048,
            allowedExtensions: 'jpg,webp',
            allowedMimeTypes: 'image/jpeg,image/webp',
            isActive: false,
            notes: 'Updated notes',
            minAspectRatio: '1.2500',
            maxAspectRatio: '2.0000',
            requiresTransparency: true,
            preferredFormat: 'webp',
            preferredQuality: 85,
            variants: '{"small":{"width":320}}',
        ));

        self::assertSame($created->id, $updated->id);
        self::assertSame('updated', $updated->code);
        self::assertSame('Updated profile', $updated->displayName);
        self::assertSame(120, $updated->minWidth);
        self::assertSame(130, $updated->minHeight);
        self::assertSame(1200, $updated->maxWidth);
        self::assertSame(1300, $updated->maxHeight);
        self::assertSame(2048, $updated->maxSizeBytes);
        self::assertSame('jpg,webp', $updated->allowedExtensions);
        self::assertSame('image/jpeg,image/webp', $updated->allowedMimeTypes);
        self::assertFalse($updated->isActive);
        self::assertSame('Updated notes', $updated->notes);
        self::assertSame('1.25', (string) (float) $updated->minAspectRatio);
        self::assertSame('2', (string) (float) $updated->maxAspectRatio);
        self::assertTrue($updated->requiresTransparency);
        self::assertSame('webp', $updated->preferredFormat);
        self::assertSame(85, $updated->preferredQuality);
        self::assertSame('{"small":{"width":320}}', $updated->variants);

        $found = $queryReader->findByCode('updated');
        self::assertNotNull($found);
        self::assertSame('Updated profile', $found->displayName);
        self::assertSame('1.25', (string) (float) $found->minAspectRatio);
    }

    public function testListProfilesAppliesSearchFiltersAndPaginationBounds(): void
    {
        $commandRepo = new PdoImageProfileCommandRepository($this->pdo);
        $queryReader = new PdoImageProfileQueryReader($this->pdo);
        $commandRepo->create(new CreateImageProfileCommand(code: 'avatar', displayName: 'User avatar'));
        $commandRepo->create(new CreateImageProfileCommand(code: 'cover', displayName: 'Cover image'));
        $inactive = $commandRepo->create(new CreateImageProfileCommand(code: 'archived', displayName: 'Archived image'));
        $commandRepo->updateStatus(new UpdateImageProfileStatusCommand($inactive->id, false));

        $search = $queryReader->listProfiles(1, 20, 'avatar', []);
        self::assertSame(1, $search->pagination->filtered);
        self::assertSame('avatar', $search->data[0]->code);

        $inactiveOnly = $queryReader->listProfiles(1, 20, null, ['is_active' => 0]);
        self::assertSame(1, $inactiveOnly->pagination->filtered);
        self::assertSame('archived', $inactiveOnly->data[0]->code);

        $bounded = $queryReader->listProfiles(0, 500, null, ['code' => 'cover']);
        self::assertSame(1, $bounded->pagination->page);
        self::assertSame(200, $bounded->pagination->perPage);
        self::assertSame(3, $bounded->pagination->total);
        self::assertSame(1, $bounded->pagination->filtered);
        self::assertSame('cover', $bounded->data[0]->code);
    }

    public function testListProfilesCanFilterByIdAndFindMissingRows(): void
    {
        $commandRepo = new PdoImageProfileCommandRepository($this->pdo);
        $queryReader = new PdoImageProfileQueryReader($this->pdo);
        $profile = $commandRepo->create(new CreateImageProfileCommand(code: 'avatar'));

        $result = $queryReader->listProfiles(1, 20, null, ['id' => $profile->id]);

        self::assertSame(1, $result->pagination->filtered);
        self::assertSame($profile->id, $result->data[0]->id);
        self::assertNull($queryReader->findById(999));
        self::assertNull($queryReader->findByCode('missing'));
    }
}
