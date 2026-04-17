<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\Infrastructure\Repository\PDO;

use Maatify\ImageProfile\Application\Contract\ImageProfileRepositoryInterface;
use Maatify\ImageProfile\Application\DTO\CreateImageProfileRequest;
use Maatify\ImageProfile\Application\DTO\UpdateImageProfileRequest;
use Maatify\ImageProfile\DTO\VariantDefinitionCollectionDTO;
use Maatify\ImageProfile\Entity\ImageProfileEntity;
use Maatify\ImageProfile\Enum\ImageFormatEnum;
use Maatify\ImageProfile\Exception\ImageProfileException;
use Maatify\ImageProfile\Exception\ImageProfileNotFoundException;
use Maatify\ImageProfile\ValueObject\AllowedExtensionCollection;
use Maatify\ImageProfile\ValueObject\AllowedMimeTypeCollection;
use PDO;
use PDOException;

/**
 * PDO-backed write repository for image profiles.
 *
 * After every mutating operation the updated row is re-fetched so the
 * returned ImageProfile entity reflects exact database state.
 */
final class PdoImageProfileRepository implements ImageProfileRepositoryInterface
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

    public function save(CreateImageProfileRequest $request): ImageProfileEntity
    {
        try {
            $sql = sprintf(
                'INSERT INTO `%s`
                    (code, display_name, min_width, min_height, max_width, max_height,
                     max_size_bytes, allowed_extensions, allowed_mime_types, is_active, notes,
                     min_aspect_ratio, max_aspect_ratio, requires_transparency,
                     preferred_format, preferred_quality, variants)
                 VALUES
                    (:code, :display_name, :min_width, :min_height, :max_width, :max_height,
                     :max_size_bytes, :allowed_extensions, :allowed_mime_types, :is_active, :notes,
                     :min_aspect_ratio, :max_aspect_ratio, :requires_transparency,
                     :preferred_format, :preferred_quality, :variants)',
                $this->table,
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':code'                  => $request->code,
                ':display_name'          => $request->displayName,
                ':min_width'             => $request->minWidth,
                ':min_height'            => $request->minHeight,
                ':max_width'             => $request->maxWidth,
                ':max_height'            => $request->maxHeight,
                ':max_size_bytes'        => $request->maxSizeBytes,
                ':allowed_extensions'    => $this->serializeExtensions($request->allowedExtensions),
                ':allowed_mime_types'    => $this->serializeMimeTypes($request->allowedMimeTypes),
                ':is_active'             => $request->isActive ? 1 : 0,
                ':notes'                 => $request->notes,
                ':min_aspect_ratio'      => $request->minAspectRatio,
                ':max_aspect_ratio'      => $request->maxAspectRatio,
                ':requires_transparency' => $request->requiresTransparency ? 1 : 0,
                ':preferred_format'      => $request->preferredFormat?->value,
                ':preferred_quality'     => $request->preferredQuality,
                ':variants'              => $this->serializeVariants($request->variants),
            ]);
        } catch (PDOException $e) {
            throw new class (
                sprintf('Failed to save image profile "%s": %s', $request->code, $e->getMessage()),
                (int) $e->getCode(),
                $e,
            ) extends ImageProfileException {};
        }

        return $this->fetchOrFail($request->code);
    }

    public function update(string $code, UpdateImageProfileRequest $request): ImageProfileEntity
    {
        $this->assertExists($code);

        try {
            $sql = sprintf(
                'UPDATE `%s` SET
                    display_name            = :display_name,
                    min_width               = :min_width,
                    min_height              = :min_height,
                    max_width               = :max_width,
                    max_height              = :max_height,
                    max_size_bytes          = :max_size_bytes,
                    allowed_extensions      = :allowed_extensions,
                    allowed_mime_types      = :allowed_mime_types,
                    notes                   = :notes,
                    min_aspect_ratio        = :min_aspect_ratio,
                    max_aspect_ratio        = :max_aspect_ratio,
                    requires_transparency   = :requires_transparency,
                    preferred_format        = :preferred_format,
                    preferred_quality       = :preferred_quality,
                    variants                = :variants
                 WHERE code = :code',
                $this->table,
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':display_name'          => $request->displayName,
                ':min_width'             => $request->minWidth,
                ':min_height'            => $request->minHeight,
                ':max_width'             => $request->maxWidth,
                ':max_height'            => $request->maxHeight,
                ':max_size_bytes'        => $request->maxSizeBytes,
                ':allowed_extensions'    => $this->serializeExtensions($request->allowedExtensions),
                ':allowed_mime_types'    => $this->serializeMimeTypes($request->allowedMimeTypes),
                ':notes'                 => $request->notes,
                ':min_aspect_ratio'      => $request->minAspectRatio,
                ':max_aspect_ratio'      => $request->maxAspectRatio,
                ':requires_transparency' => $request->requiresTransparency ? 1 : 0,
                ':preferred_format'      => $request->preferredFormat?->value,
                ':preferred_quality'     => $request->preferredQuality,
                ':variants'              => $this->serializeVariants($request->variants),
                ':code'                  => $code,
            ]);
        } catch (PDOException $e) {
            throw new class (
                sprintf('Failed to update image profile "%s": %s', $code, $e->getMessage()),
                (int) $e->getCode(),
                $e,
            ) extends ImageProfileException {};
        }

        return $this->fetchOrFail($code);
    }

    public function toggleActive(string $code, bool $isActive): ImageProfileEntity
    {
        $this->assertExists($code);

        try {
            $sql = sprintf(
                'UPDATE `%s` SET is_active = :is_active WHERE code = :code',
                $this->table,
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':is_active' => $isActive ? 1 : 0,
                ':code'      => $code,
            ]);
        } catch (PDOException $e) {
            throw new class (
                sprintf('Failed to toggle active state for profile "%s": %s', $code, $e->getMessage()),
                (int) $e->getCode(),
                $e,
            ) extends ImageProfileException {};
        }

        return $this->fetchOrFail($code);
    }

    public function existsByCode(string $code): bool
    {
        try {
            $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE code = :code', $this->table);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':code' => $code]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new class (
                sprintf('Failed to check existence for profile "%s": %s', $code, $e->getMessage()),
                (int) $e->getCode(),
                $e,
            ) extends ImageProfileException {};
        }
    }

    private function fetchOrFail(string $code): ImageProfileEntity
    {
        try {
            $sql = sprintf(
                'SELECT %s FROM `%s` WHERE code = :code LIMIT 1',
                self::COLUMNS,
                $this->table,
            );

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':code' => $code]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new class (
                sprintf('Failed to fetch profile "%s" after mutation: %s', $code, $e->getMessage()),
                (int) $e->getCode(),
                $e,
            ) extends ImageProfileException {};
        }

        if (!is_array($row)) {
            throw ImageProfileNotFoundException::forCode($code);
        }

        /** @var array{
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
         * } $row
         */
        return $this->mapRow($row);
    }

    private function assertExists(string $code): void
    {
        if (!$this->existsByCode($code)) {
            throw ImageProfileNotFoundException::forCode($code);
        }
    }

    /**
     * @param array{
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
     * } $row
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

    private function serializeExtensions(AllowedExtensionCollection $collection): ?string
    {
        if ($collection->isEmpty()) {
            return null;
        }

        $parts = [];
        foreach ($collection as $value) {
            $parts[] = $value;
        }

        return implode(',', $parts);
    }

    private function serializeMimeTypes(AllowedMimeTypeCollection $collection): ?string
    {
        if ($collection->isEmpty()) {
            return null;
        }

        $parts = [];
        foreach ($collection as $value) {
            $parts[] = $value;
        }

        return implode(',', $parts);
    }

    private function serializeVariants(VariantDefinitionCollectionDTO $variants): ?string
    {
        if (count($variants) === 0) {
            return null;
        }

        return json_encode($variants, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
