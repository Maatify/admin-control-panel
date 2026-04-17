<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Infrastructure\Persistence\PDO;

use Maatify\ImageProfile\Contract\ImageProfileProviderInterface;
use Maatify\ImageProfile\DTO\ImageProfileCollectionDTO;
use Maatify\ImageProfile\DTO\VariantDefinitionCollectionDTO;
use Maatify\ImageProfile\Entity\ImageProfileEntity;
use Maatify\ImageProfile\Enum\ImageFormatEnum;
use Maatify\ImageProfile\Exception\ImageProfileException;
use Maatify\ImageProfile\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection;
use PDO;
use PDOException;

/**
 * Database-backed profile provider using a plain PDO connection.
 *
 * Active-state filtering:
 *   `findByCode` returns ANY profile regardless of `is_active`.
 *   The validator decides whether to accept or reject an inactive profile.
 *   `listActive` applies the filter at the SQL level for efficiency.
 *
 * @phpstan-type ImageProfileRow array{
 *     id?: int|string|null,
 *     code: string,
 *     display_name?: string|null,
 *     min_width?: int|string|null,
 *     min_height?: int|string|null,
 *     max_width?: int|string|null,
 *     max_height?: int|string|null,
 *     max_size_bytes?: int|string|null,
 *     allowed_extensions?: string|null,
 *     allowed_mime_types?: string|null,
 *     is_active?: int|string|bool|null,
 *     notes?: string|null,
 *     min_aspect_ratio?: float|int|string|null,
 *     max_aspect_ratio?: float|int|string|null,
 *     requires_transparency?: int|string|bool|null,
 *     preferred_format?: string|null,
 *     preferred_quality?: int|string|null,
 *     variants?: string|null
 * }
 */
final class PdoImageProfileProvider implements ImageProfileProviderInterface
{
    private const COLUMNS = 'id, code, display_name, min_width, min_height,
        max_width, max_height, max_size_bytes, allowed_extensions,
        allowed_mime_types, is_active, notes,
        min_aspect_ratio, max_aspect_ratio, requires_transparency,
        preferred_format, preferred_quality, variants';

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table = 'image_profiles',
    ) {
    }

    /**
     * @throws ImageProfileException on database / infrastructure failure
     */
    public function findByCode(string $code): ?ImageProfileEntity
    {
        try {
            $sql = sprintf(
                'SELECT %s FROM `%s` WHERE `code` = :code LIMIT 1',
                self::COLUMNS,
                $this->table,
            );
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':code' => $code]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new class (
                sprintf('Failed to fetch image profile by code "%s": %s', $code, $e->getMessage()),
                (int) $e->getCode(),
                $e,
            ) extends ImageProfileException {};
        }

        if (!is_array($row)) {
            return null;
        }

        /** @var ImageProfileRow $row */
        return $this->mapRow($row);
    }

    /**
     * @throws ImageProfileException on database / infrastructure failure
     */
    public function listAll(): ImageProfileCollectionDTO
    {
        try {
            $sql = sprintf(
                'SELECT %s FROM `%s` ORDER BY `id` ASC',
                self::COLUMNS,
                $this->table,
            );
            $stmt = $this->pdo->query($sql);
            if ($stmt === false) {
                throw new class('Query failed: listAll') extends ImageProfileException {};
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new class (
                sprintf('Failed to list image profiles: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e,
            ) extends ImageProfileException {};
        }

        /** @var list<ImageProfileRow> $rows */
        return new ImageProfileCollectionDTO(...array_map([$this, 'mapRow'], $rows));
    }

    /**
     * @throws ImageProfileException on database / infrastructure failure
     */
    public function listActive(): ImageProfileCollectionDTO
    {
        try {
            $sql = sprintf(
                'SELECT %s FROM `%s` WHERE `is_active` = 1 ORDER BY `id` ASC',
                self::COLUMNS,
                $this->table,
            );
            $stmt = $this->pdo->query($sql);
            if ($stmt === false) {
                throw new class('Query failed: listActive') extends ImageProfileException {};
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new class (
                sprintf('Failed to list active image profiles: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e,
            ) extends ImageProfileException {};
        }

        /** @var list<ImageProfileRow> $rows */
        return new ImageProfileCollectionDTO(...array_map([$this, 'mapRow'], $rows));
    }

    /**
     * @param ImageProfileRow $row
     */
    private function mapRow(array $row): ImageProfileEntity
    {
        return new ImageProfileEntity(
            id: isset($row['id']) ? (int) $row['id'] : null,
            code: $row['code'],
            displayName: $row['display_name'] ?? null,
            minWidth: isset($row['min_width']) ? (int) $row['min_width'] : null,
            minHeight: isset($row['min_height']) ? (int) $row['min_height'] : null,
            maxWidth: isset($row['max_width']) ? (int) $row['max_width'] : null,
            maxHeight: isset($row['max_height']) ? (int) $row['max_height'] : null,
            maxSizeBytes: isset($row['max_size_bytes']) ? (int) $row['max_size_bytes'] : null,
            allowedExtensions: AllowedExtensionCollection::fromDelimitedString(
                $row['allowed_extensions'] ?? null,
            ),
            allowedMimeTypes: AllowedMimeTypeCollection::fromDelimitedString(
                $row['allowed_mime_types'] ?? null,
            ),
            isActive: (bool) ($row['is_active'] ?? false),
            notes: $row['notes'] ?? null,
            minAspectRatio: isset($row['min_aspect_ratio']) ? (float) $row['min_aspect_ratio'] : null,
            maxAspectRatio: isset($row['max_aspect_ratio']) ? (float) $row['max_aspect_ratio'] : null,
            requiresTransparency: (bool) ($row['requires_transparency'] ?? false),
            preferredFormat: isset($row['preferred_format'])
                ? ImageFormatEnum::tryFrom($row['preferred_format'])
                : null,
            preferredQuality: isset($row['preferred_quality']) ? (int) $row['preferred_quality'] : null,
            variants: VariantDefinitionCollectionDTO::fromJsonString(
                $row['variants'] ?? null,
            ),
        );
    }
}
