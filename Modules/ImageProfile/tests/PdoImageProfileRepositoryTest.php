<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Tests;

use Maatify\ImageProfile\Command\CreateImageProfileCommand;
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
}
