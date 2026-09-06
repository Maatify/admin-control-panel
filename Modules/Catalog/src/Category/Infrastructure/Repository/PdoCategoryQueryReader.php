<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Infrastructure\Repository;

use DateTimeImmutable;
use DateTimeZone;
use Maatify\Catalog\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationDTO;
use Maatify\Catalog\Category\Enum\CategoryStatusEnum;
use Maatify\Catalog\Category\Exception\CategoryPersistenceException;
use PDO;

/** PDO read adapter with explicit active/all-state and locking semantics. */
final readonly class PdoCategoryQueryReader implements CategoryQueryReaderInterface
{
    private const CATEGORY_TABLE = 'maa_catalog_categories';
    private const TRANSLATION_TABLE = 'maa_catalog_category_translations';

    public function __construct(private PDO $pdo) {}

    public function findById(int $categoryId): ?CategoryDTO
    {
        return $this->findCategory($categoryId, false, false);
    }

    public function findByCode(string $code): ?CategoryDTO
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `parent_id`, `code`, `status`, `display_order`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `code` = :code LIMIT 1',
        );
        $statement->execute(['code' => $code]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateCategory($row) : null;
    }

    public function findActiveById(int $categoryId): ?CategoryDTO
    {
        return $this->findCategory($categoryId, true, false);
    }

    public function findActiveByIdForUpdate(int $categoryId): ?CategoryDTO
    {
        return $this->findCategory($categoryId, true, true);
    }

    public function findByIdForUpdate(int $categoryId): ?CategoryDTO
    {
        return $this->findCategory($categoryId, false, true);
    }

    public function hasNonDeletedChildrenForUpdate(int $categoryId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT `id` FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `parent_id` = :parent_id AND `deleted_at` IS NULL '
            . 'FOR UPDATE',
        );
        $statement->execute(['parent_id' => $categoryId]);

        return $statement->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function findTranslationById(int $translationId): ?CategoryTranslationDTO
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `category_id`, `language_code`, `name`, `description`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::TRANSLATION_TABLE . '` '
            . 'WHERE `id` = :id LIMIT 1',
        );
        $statement->execute(['id' => $translationId]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateTranslation($row) : null;
    }

    private function findCategory(int $categoryId, bool $activeOnly, bool $forUpdate): ?CategoryDTO
    {
        $where = '`id` = :id';
        if ($activeOnly) {
            $where .= ' AND `deleted_at` IS NULL';
        }

        $statement = $this->pdo->prepare(
            'SELECT `id`, `parent_id`, `code`, `status`, `display_order`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE ' . $where . ' LIMIT 1'
            . ($forUpdate ? ' FOR UPDATE' : ''),
        );
        $statement->execute(['id' => $categoryId]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateCategory($row) : null;
    }

    /** @param array<string, mixed> $row */
    private function hydrateCategory(array $row): CategoryDTO
    {
        $status = $row['status'] ?? null;
        if (!is_string($status)) {
            throw CategoryPersistenceException::unexpectedColumnType('status');
        }

        $parentId = $row['parent_id'] ?? null;
        if ($parentId !== null && !is_int($parentId) && !is_string($parentId)) {
            throw CategoryPersistenceException::unexpectedColumnType('parent_id');
        }
        try {
            $categoryStatus = CategoryStatusEnum::from($status);
        } catch (\ValueError $exception) {
            throw CategoryPersistenceException::invalidStorageValue('status', $exception);
        }

        return new CategoryDTO(
            id: $this->integerValue($row, 'id'),
            parentId: $parentId === null ? null : (int) $parentId,
            code: $this->stringValue($row, 'code'),
            status: $categoryStatus,
            displayOrder: $this->integerValue($row, 'display_order'),
            createdAt: $this->timestampValue($row, 'created_at'),
            updatedAt: $this->timestampValue($row, 'updated_at'),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at'),
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrateTranslation(array $row): CategoryTranslationDTO
    {
        return new CategoryTranslationDTO(
            id: $this->integerValue($row, 'id'),
            categoryId: $this->integerValue($row, 'category_id'),
            languageCode: $this->stringValue($row, 'language_code'),
            name: $this->stringValue($row, 'name'),
            description: $this->nullableStringValue($row, 'description'),
            createdAt: $this->timestampValue($row, 'created_at'),
            updatedAt: $this->timestampValue($row, 'updated_at'),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at'),
        );
    }

    /** @param array<string, mixed> $row */
    private function integerValue(array $row, string $column): int
    {
        $value = $row[$column] ?? null;
        if (!is_int($value) && !is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        return (int) $value;
    }

    /** @param array<string, mixed> $row */
    private function stringValue(array $row, string $column): string
    {
        $value = $row[$column] ?? null;
        if (!is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        return $value;
    }

    /** @param array<string, mixed> $row */
    private function nullableStringValue(array $row, string $column): ?string
    {
        $value = $row[$column] ?? null;
        if ($value !== null && !is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        return $value;
    }

    /** @param array<string, mixed> $row */
    private function timestampValue(array $row, string $column): DateTimeImmutable
    {
        $value = $this->stringValue($row, $column);

        try {
            return new DateTimeImmutable($value, new DateTimeZone('UTC'));
        } catch (\Exception $exception) {
            throw CategoryPersistenceException::invalidStorageValue($column, $exception);
        }
    }

    /** @param array<string, mixed> $row */
    private function nullableTimestampValue(array $row, string $column): ?DateTimeImmutable
    {
        $value = $row[$column] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        try {
            return new DateTimeImmutable($value, new DateTimeZone('UTC'));
        } catch (\Exception $exception) {
            throw CategoryPersistenceException::invalidStorageValue($column, $exception);
        }
    }
}
