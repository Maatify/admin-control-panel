<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Infrastructure\Repository;

use DateTimeImmutable;
use DateTimeZone;
use Maatify\Catalog\Category\Contract\CategoryReadQueryInterface;
use Maatify\Catalog\Category\DTO\CategoryCollectionDTO;
use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationCollectionDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationDTO;
use Maatify\Catalog\Category\Enum\CategoryStatusEnum;
use PDO;
use RuntimeException;

/** Dedicated PDO adapter for visible Category query behavior. */
final readonly class PdoCategoryReadQuery implements CategoryReadQueryInterface
{
    private const CATEGORY_TABLE = 'maa_catalog_categories';
    private const TRANSLATION_TABLE = 'maa_catalog_category_translations';

    public function __construct(private PDO $pdo) {}

    public function findVisibleById(int $categoryId): ?CategoryDTO
    {
        $statement = $this->pdo->prepare(
            'WITH RECURSIVE `category_ancestors` AS ('
            . 'SELECT `id`, `parent_id`, `status`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `id` = :ancestor_category_id '
            . 'UNION ALL '
            . 'SELECT `parent`.`id`, `parent`.`parent_id`, `parent`.`status`, `parent`.`deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `parent` '
            . 'INNER JOIN `category_ancestors` AS `child` '
            . 'ON `child`.`parent_id` = `parent`.`id`'
            . ') '
            . 'SELECT `id`, `parent_id`, `code`, `status`, `display_order`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `category` '
            . 'WHERE `category`.`id` = :visible_category_id '
            . 'AND `category`.`status` = \'active\' '
            . 'AND `category`.`deleted_at` IS NULL '
            . 'AND NOT EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `ancestor` '
            . 'WHERE `ancestor`.`status` <> \'active\' '
            . 'OR `ancestor`.`deleted_at` IS NOT NULL'
            . ') '
            . 'LIMIT 1',
        );
        $statement->execute([
            'ancestor_category_id' => $categoryId,
            'visible_category_id' => $categoryId,
        ]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateCategory($row) : null;
    }

    public function listVisibleRootCategories(): CategoryCollectionDTO
    {
        $statement = $this->pdo->query(
            'SELECT `id`, `parent_id`, `code`, `status`, `display_order`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `parent_id` IS NULL '
            . 'AND `status` = \'active\' '
            . 'AND `deleted_at` IS NULL '
            . 'ORDER BY `display_order` ASC, `id` ASC',
        );
        if ($statement === false) {
            throw new RuntimeException('Unable to query visible root Categories.');
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $this->hydrateCategories($rows);
    }

    public function listVisibleChildren(int $parentId): CategoryCollectionDTO
    {
        $statement = $this->pdo->prepare(
            'WITH RECURSIVE `category_ancestors` AS ('
            . 'SELECT `id`, `parent_id`, `status`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `id` = :ancestor_start_id '
            . 'UNION ALL '
            . 'SELECT `parent`.`id`, `parent`.`parent_id`, `parent`.`status`, `parent`.`deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `parent` '
            . 'INNER JOIN `category_ancestors` AS `child` '
            . 'ON `child`.`parent_id` = `parent`.`id`'
            . ') '
            . 'SELECT `child`.`id`, `child`.`parent_id`, `child`.`code`, `child`.`status`, '
            . '`child`.`display_order`, `child`.`created_at`, `child`.`updated_at`, `child`.`deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `child` '
            . 'WHERE `child`.`parent_id` = :children_parent_id '
            . 'AND `child`.`status` = \'active\' '
            . 'AND `child`.`deleted_at` IS NULL '
            . 'AND EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `requested_parent` '
            . 'WHERE `requested_parent`.`id` = :requested_parent_id'
            . ') '
            . 'AND NOT EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `ancestor` '
            . 'WHERE `ancestor`.`status` <> \'active\' '
            . 'OR `ancestor`.`deleted_at` IS NOT NULL'
            . ') '
            . 'ORDER BY `child`.`display_order` ASC, `child`.`id` ASC',
        );
        $statement->execute([
            'ancestor_start_id' => $parentId,
            'children_parent_id' => $parentId,
            'requested_parent_id' => $parentId,
        ]);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $this->hydrateCategories($rows);
    }

    public function listVisibleTranslations(int $categoryId): CategoryTranslationCollectionDTO
    {
        $statement = $this->pdo->prepare(
            'WITH RECURSIVE `category_ancestors` AS ('
            . 'SELECT `id`, `parent_id`, `status`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `id` = :translation_ancestor_start_id '
            . 'UNION ALL '
            . 'SELECT `parent`.`id`, `parent`.`parent_id`, `parent`.`status`, `parent`.`deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `parent` '
            . 'INNER JOIN `category_ancestors` AS `child` '
            . 'ON `child`.`parent_id` = `parent`.`id`'
            . ') '
            . 'SELECT `translation`.`id`, `translation`.`category_id`, '
            . '`translation`.`language_code`, `translation`.`name`, `translation`.`description`, '
            . '`translation`.`created_at`, `translation`.`updated_at`, `translation`.`deleted_at` '
            . 'FROM `' . self::TRANSLATION_TABLE . '` AS `translation` '
            . 'WHERE `translation`.`category_id` = :translation_category_id '
            . 'AND `translation`.`deleted_at` IS NULL '
            . 'AND EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `visible_category` '
            . 'WHERE `visible_category`.`id` = :visible_translation_category_id'
            . ') '
            . 'AND NOT EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `ancestor` '
            . 'WHERE `ancestor`.`status` <> \'active\' '
            . 'OR `ancestor`.`deleted_at` IS NOT NULL'
            . ') '
            . 'ORDER BY `translation`.`language_code` ASC, `translation`.`id` ASC',
        );
        $statement->execute([
            'translation_ancestor_start_id' => $categoryId,
            'translation_category_id' => $categoryId,
            'visible_translation_category_id' => $categoryId,
        ]);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $this->hydrateTranslations($rows);
    }

    /** @param array<string, mixed> $row */
    private function hydrateCategory(array $row): CategoryDTO
    {
        $status = $row['status'] ?? null;
        if (!is_string($status)) {
            throw new RuntimeException('Catalog Category status storage value is invalid.');
        }

        $parentId = $row['parent_id'] ?? null;
        if ($parentId !== null && !is_int($parentId) && !is_string($parentId)) {
            throw new RuntimeException('Catalog Category parent identity storage value is invalid.');
        }

        return new CategoryDTO(
            id: $this->integerValue($row, 'id'),
            parentId: $parentId === null ? null : (int) $parentId,
            code: $this->stringValue($row, 'code'),
            status: CategoryStatusEnum::from($status),
            displayOrder: $this->integerValue($row, 'display_order'),
            createdAt: $this->timestampValue($row, 'created_at'),
            updatedAt: $this->timestampValue($row, 'updated_at'),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at'),
        );
    }

    /** @param list<array<string, mixed>> $rows */
    private function hydrateCategories(array $rows): CategoryCollectionDTO
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateCategory($row);
        }

        /** @var list<CategoryDTO> $items */
        return new CategoryCollectionDTO($items);
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

    /** @param list<array<string, mixed>> $rows */
    private function hydrateTranslations(array $rows): CategoryTranslationCollectionDTO
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateTranslation($row);
        }

        /** @var list<CategoryTranslationDTO> $items */
        return new CategoryTranslationCollectionDTO($items);
    }

    /** @param array<string, mixed> $row */
    private function integerValue(array $row, string $column): int
    {
        $value = $row[$column] ?? null;
        if (!is_int($value) && !is_string($value)) {
            throw new RuntimeException(sprintf('Catalog storage column "%s" is invalid.', $column));
        }

        return (int) $value;
    }

    /** @param array<string, mixed> $row */
    private function stringValue(array $row, string $column): string
    {
        $value = $row[$column] ?? null;
        if (!is_string($value)) {
            throw new RuntimeException(sprintf('Catalog storage column "%s" is invalid.', $column));
        }

        return $value;
    }

    /** @param array<string, mixed> $row */
    private function nullableStringValue(array $row, string $column): ?string
    {
        $value = $row[$column] ?? null;
        if ($value !== null && !is_string($value)) {
            throw new RuntimeException(sprintf('Catalog storage column "%s" is invalid.', $column));
        }

        return $value;
    }

    /** @param array<string, mixed> $row */
    private function timestampValue(array $row, string $column): DateTimeImmutable
    {
        $value = $this->stringValue($row, $column);

        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }

    /** @param array<string, mixed> $row */
    private function nullableTimestampValue(array $row, string $column): ?DateTimeImmutable
    {
        $value = $row[$column] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw new RuntimeException(sprintf('Catalog storage column "%s" is invalid.', $column));
        }

        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
