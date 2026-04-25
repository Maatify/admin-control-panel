<?php

declare(strict_types=1);

namespace Maatify\Category\Infrastructure\Repository;

use Maatify\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryImageDTO;
use Maatify\Category\DTO\CategorySettingDTO;
use Maatify\Category\DTO\CategoryTranslationDTO;
use Maatify\Category\Enum\CategoryImageTypeEnum;
use Maatify\Category\Exception\CategoryPersistenceException;
use PDO;
use PDOStatement;

final class PdoCategoryQueryReader implements CategoryQueryReaderInterface
{
    /**
     * SELECT fragment shared by all translation queries.
     * Always aliased so CategoryTranslationDTO::fromRow() gets consistent keys.
     */
    private const TRANSLATION_SELECT = '
        ct.id,
        l.id          AS language_id,
        l.code        AS language_code,
        l.name        AS language_name,
        ct.name,
        ct.description,
        ct.created_at,
        ct.updated_at
    ';

    public function __construct(private readonly PDO $pdo) {}

    // ================================================================== //
    //  Admin list — all categories (root + sub)
    // ================================================================== //

    /**
     * {@inheritDoc}
     *
     * @param  array<string, int|string> $columnFilters
     */
    public function listCategories(
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array {
        $page   = max(1, $page);
        $limit  = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        $where  = [];
        $params = [];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $where[]               = '(c.`name` LIKE :global_text OR c.`slug` LIKE :global_text)';
            $params['global_text'] = '%' . $this->escapeLike(trim($globalSearch)) . '%';
        }

        if (isset($columnFilters['id'])) {
            $where[]      = 'c.`id` = :id';
            $params['id'] = (int) $columnFilters['id'];
        }

        if (isset($columnFilters['is_active'])) {
            $where[]             = 'c.`is_active` = :is_active';
            $params['is_active'] = (int) $columnFilters['is_active'];
        }

        if (isset($columnFilters['parent_id'])) {
            $parentFilter = (int) $columnFilters['parent_id'];
            if ($parentFilter === 0) {
                // Special sentinel: list root categories only
                $where[] = 'c.`parent_id` IS NULL';
            } else {
                $where[]              = 'c.`parent_id` = :parent_id';
                $params['parent_id']  = $parentFilter;
            }
        }

        if (isset($columnFilters['name'])) {
            $where[]        = 'c.`name` LIKE :name';
            $params['name'] = '%' . $this->escapeLike((string) $columnFilters['name']) . '%';
        }

        if (isset($columnFilters['slug'])) {
            $where[]        = 'c.`slug` LIKE :slug';
            $params['slug'] = '%' . $this->escapeLike((string) $columnFilters['slug']) . '%';
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $total    = $this->scalarInt('SELECT COUNT(*) FROM `maa_categories`');

        $stmtFiltered = $this->prepareOrFail("SELECT COUNT(*) FROM `maa_categories` AS c {$whereSql}");
        foreach ($params as $key => $value) {
            $stmtFiltered->bindValue(':' . $key, $value);
        }
        $stmtFiltered->execute();
        $filtered = (int) $stmtFiltered->fetchColumn();

        $stmt = $this->prepareOrFail("
            SELECT c.*,
                   (SELECT COUNT(*) FROM `maa_categories` WHERE `parent_id` = c.`id`) AS `child_count`
            FROM   `maa_categories` AS c
            {$whereSql}
            ORDER BY c.`display_order` ASC, c.`id` ASC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<CategoryDTO> $data */
        $data = array_map(
            static fn (array $row): CategoryDTO => CategoryDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return [
            'data'       => $data,
            'pagination' => [
                'page'     => $page,
                'per_page' => $limit,
                'total'    => $total,
                'filtered' => $filtered,
            ],
        ];
    }

    // ================================================================== //
    //  Admin list — sub-categories of a specific parent
    // ================================================================== //

    /**
     * {@inheritDoc}
     *
     * @param  array<string, int|string> $columnFilters
     */
    public function listSubCategories(
        int     $parentId,
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array {
        $page   = max(1, $page);
        $limit  = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        // Base condition is always parent-scoped
        $where  = ['c.`parent_id` = :parent_id'];
        $params = ['parent_id' => $parentId];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $where[]               = '(c.`name` LIKE :global_text OR c.`slug` LIKE :global_text)';
            $params['global_text'] = '%' . $this->escapeLike(trim($globalSearch)) . '%';
        }

        if (isset($columnFilters['is_active'])) {
            $where[]             = 'c.`is_active` = :is_active';
            $params['is_active'] = (int) $columnFilters['is_active'];
        }

        if (isset($columnFilters['name'])) {
            $where[]        = 'c.`name` LIKE :name';
            $params['name'] = '%' . $this->escapeLike((string) $columnFilters['name']) . '%';
        }

        if (isset($columnFilters['slug'])) {
            $where[]        = 'c.`slug` LIKE :slug';
            $params['slug'] = '%' . $this->escapeLike((string) $columnFilters['slug']) . '%';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $stmtTotal = $this->prepareOrFail('SELECT COUNT(*) FROM `maa_categories` WHERE `parent_id` = :parent_id');
        $stmtTotal->execute([':parent_id' => $parentId]);
        $total = (int) $stmtTotal->fetchColumn();

        $stmtFiltered = $this->prepareOrFail("SELECT COUNT(*) FROM `maa_categories` AS c {$whereSql}");
        foreach ($params as $key => $value) {
            $stmtFiltered->bindValue(':' . $key, $value);
        }
        $stmtFiltered->execute();
        $filtered = (int) $stmtFiltered->fetchColumn();

        // Sub-categories cannot have children — child_count is always 0
        $stmt = $this->prepareOrFail("
            SELECT c.*, 0 AS `child_count`
            FROM   `maa_categories` AS c
            {$whereSql}
            ORDER BY c.`display_order` ASC, c.`id` ASC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<CategoryDTO> $data */
        $data = array_map(
            static fn (array $row): CategoryDTO => CategoryDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return [
            'data'       => $data,
            'pagination' => [
                'page'     => $page,
                'per_page' => $limit,
                'total'    => $total,
                'filtered' => $filtered,
            ],
        ];
    }

    // ================================================================== //
    //  Website list — active only, no pagination
    // ================================================================== //

    /** {@inheritDoc} */
    public function listActiveRootCategories(): array
    {
        $stmt = $this->prepareOrFail('
            SELECT c.*,
                   (SELECT COUNT(*) FROM `maa_categories` WHERE `parent_id` = c.`id`) AS `child_count`
            FROM   `maa_categories` AS c
            WHERE  c.`is_active` = 1
              AND  c.`parent_id` IS NULL
            ORDER BY c.`display_order` ASC, c.`id` ASC
        ');
        $stmt->execute();

        /** @var list<CategoryDTO> */
        return array_map(
            static fn (array $row): CategoryDTO => CategoryDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );
    }

    /** {@inheritDoc} */
    public function listActiveSubCategories(int $parentId): array
    {
        $stmt = $this->prepareOrFail('
            SELECT c.*, 0 AS `child_count`
            FROM   `maa_categories` AS c
            WHERE  c.`is_active`  = 1
              AND  c.`parent_id`  = ?
            ORDER BY c.`display_order` ASC, c.`id` ASC
        ');
        $stmt->execute([$parentId]);

        /** @var list<CategoryDTO> */
        return array_map(
            static fn (array $row): CategoryDTO => CategoryDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );
    }

    // ================================================================== //
    //  Single-record lookups
    // ================================================================== //

    /** {@inheritDoc} */
    public function findById(int $id): ?CategoryDTO
    {
        $stmt = $this->prepareOrFail('
            SELECT c.*,
                   (SELECT COUNT(*) FROM `maa_categories` WHERE `parent_id` = c.`id`) AS `child_count`
            FROM   `maa_categories` AS c
            WHERE  c.`id` = ?
            LIMIT  1
        ');
        $stmt->execute([$id]);

        $row = $this->fetchAssoc($stmt);

        return $row !== null ? CategoryDTO::fromRow($row) : null;
    }

    /** {@inheritDoc} */
    public function findBySlug(string $slug): ?CategoryDTO
    {
        $stmt = $this->prepareOrFail('
            SELECT c.*,
                   (SELECT COUNT(*) FROM `maa_categories` WHERE `parent_id` = c.`id`) AS `child_count`
            FROM   `maa_categories` AS c
            WHERE  c.`slug` = ?
            LIMIT  1
        ');
        $stmt->execute([$slug]);

        $row = $this->fetchAssoc($stmt);

        return $row !== null ? CategoryDTO::fromRow($row) : null;
    }

    // ================================================================== //
    //  Aggregates
    // ================================================================== //

    /** {@inheritDoc} */
    public function maxDisplayOrder(?int $parentId): int
    {
        if ($parentId === null) {
            return $this->scalarInt(
                'SELECT COALESCE(MAX(`display_order`), 0) FROM `maa_categories` WHERE `parent_id` IS NULL',
            );
        }

        return $this->scalarInt(
            'SELECT COALESCE(MAX(`display_order`), 0) FROM `maa_categories` WHERE `parent_id` = ?',
            [$parentId],
        );
    }

    // ================================================================== //
    //  Settings
    // ================================================================== //

    /** {@inheritDoc} */
    public function listSettings(
        int     $categoryId,
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array {
        $page   = max(1, $page);
        $limit  = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        $where  = ['s.`category_id` = :category_id'];
        $params = ['category_id' => $categoryId];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $where[]               = '(s.`key` LIKE :global_text OR s.`value` LIKE :global_text)';
            $params['global_text'] = '%' . $this->escapeLike(trim($globalSearch)) . '%';
        }

        if (isset($columnFilters['key'])) {
            $where[]      = 's.`key` LIKE :key';
            $params['key'] = '%' . $this->escapeLike((string) $columnFilters['key']) . '%';
        }

        if (isset($columnFilters['value'])) {
            $where[]          = 's.`value` LIKE :value';
            $params['value']  = '%' . $this->escapeLike((string) $columnFilters['value']) . '%';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $stmtTotal = $this->prepareOrFail('SELECT COUNT(*) FROM `maa_category_settings` WHERE `category_id` = :category_id');
        $stmtTotal->execute([':category_id' => $categoryId]);
        $total = (int) $stmtTotal->fetchColumn();

        $stmtFiltered = $this->prepareOrFail("SELECT COUNT(*) FROM `maa_category_settings` AS s {$whereSql}");
        foreach ($params as $key => $value) {
            $stmtFiltered->bindValue(':' . $key, $value);
        }
        $stmtFiltered->execute();
        $filtered = (int) $stmtFiltered->fetchColumn();

        $stmt = $this->prepareOrFail("
            SELECT s.*
            FROM   `maa_category_settings` AS s
            {$whereSql}
            ORDER BY s.`key` ASC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<CategorySettingDTO> $data */
        $data = array_map(
            static fn (array $row): CategorySettingDTO => CategorySettingDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return [
            'data'       => $data,
            'pagination' => [
                'page'     => $page,
                'per_page' => $limit,
                'total'    => $total,
                'filtered' => $filtered,
            ],
        ];
    }

    /** {@inheritDoc} */
    public function findSetting(int $categoryId, string $key): ?CategorySettingDTO
    {
        $stmt = $this->prepareOrFail(
            'SELECT * FROM `maa_category_settings` WHERE `category_id` = ? AND `key` = ? LIMIT 1',
        );
        $stmt->execute([$categoryId, $key]);

        $row = $this->fetchAssoc($stmt);

        return $row !== null ? CategorySettingDTO::fromRow($row) : null;
    }

    // ================================================================== //
    //  Images
    // ================================================================== //

    /** {@inheritDoc} */
    public function listImages(int $categoryId): array
    {
        $stmt = $this->prepareOrFail(
            'SELECT * FROM `maa_category_images`
             WHERE  `category_id` = ?
             ORDER BY `image_type` ASC, `language_id` ASC',
        );
        $stmt->execute([$categoryId]);

        /** @var array<string, list<CategoryImageDTO>> $grouped */
        $grouped = [];

        // Pre-seed all four slots so every key is always present
        foreach (CategoryImageTypeEnum::cases() as $case) {
            $grouped[$case->value] = [];
        }

        foreach ($this->fetchAllAssoc($stmt) as $row) {
            $dto = CategoryImageDTO::fromRow($row);
            $grouped[$dto->imageType][] = $dto;
        }

        return $grouped;
    }

    /** {@inheritDoc} */
    public function findImage(int $categoryId, CategoryImageTypeEnum $imageType, int $languageId): ?CategoryImageDTO
    {
        $stmt = $this->prepareOrFail(
            'SELECT * FROM `maa_category_images`
             WHERE `category_id` = ? AND `image_type` = ? AND `language_id` = ?
             LIMIT 1',
        );
        $stmt->execute([$categoryId, $imageType->value, $languageId]);

        $row = $this->fetchAssoc($stmt);

        return $row !== null ? CategoryImageDTO::fromRow($row) : null;
    }

    // ================================================================== //
    //  Translations
    // ================================================================== //

    /** {@inheritDoc} */
    public function findTranslation(int $categoryId, int $languageId): ?CategoryTranslationDTO
    {
        $stmt = $this->prepareOrFail(
            'SELECT ' . self::TRANSLATION_SELECT . '
             FROM `maa_category_translations` ct
             INNER JOIN `languages` l ON l.id = ct.`language_id`
             WHERE ct.`category_id` = ? AND ct.`language_id` = ?
             LIMIT 1',
        );
        $stmt->execute([$categoryId, $languageId]);

        $row = $this->fetchAssoc($stmt);

        return $row !== null ? CategoryTranslationDTO::fromRow($row) : null;
    }

    /** {@inheritDoc} */
    public function listTranslationsForCategoryPaginated(
        int     $categoryId,
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array {
        $page   = max(1, $page);
        $limit  = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        $where  = [];
        $params = [];

        // Always scope to active languages
        $where[] = 'l.`is_active` = 1';

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $where[]               = '(l.`name` LIKE :global_text OR l.`code` LIKE :global_text OR ct.`name` LIKE :global_text OR ct.`description` LIKE :global_text)';
            $params['global_text'] = '%' . $this->escapeLike(trim($globalSearch)) . '%';
        }

        if (isset($columnFilters['language_id'])) {
            $where[]               = 'l.`id` = :language_id';
            $params['language_id'] = (int) $columnFilters['language_id'];
        }

        if (isset($columnFilters['language_code'])) {
            $where[]                 = 'l.`code` LIKE :language_code';
            $params['language_code'] = '%' . $this->escapeLike((string) $columnFilters['language_code']) . '%';
        }

        if (isset($columnFilters['language_name'])) {
            $where[]                 = 'l.`name` LIKE :language_name';
            $params['language_name'] = '%' . $this->escapeLike((string) $columnFilters['language_name']) . '%';
        }

        if (isset($columnFilters['name'])) {
            $where[]              = 'ct.`name` LIKE :trans_name';
            $params['trans_name'] = '%' . $this->escapeLike((string) $columnFilters['name']) . '%';
        }

        if (isset($columnFilters['description'])) {
            $where[]              = 'ct.`description` LIKE :trans_desc';
            $params['trans_desc'] = '%' . $this->escapeLike((string) $columnFilters['description']) . '%';
        }

        if (isset($columnFilters['has_translation'])) {
            $val = (string) $columnFilters['has_translation'];
            if ($val === '1') {
                $where[] = 'ct.`id` IS NOT NULL';
            } elseif ($val === '0') {
                $where[] = 'ct.`id` IS NULL';
            }
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // Total — all active languages
        $stmtTotal = $this->prepareOrFail('
            SELECT COUNT(*)
            FROM `languages` l
            LEFT JOIN `maa_category_translations` ct
                   ON ct.`language_id` = l.`id`
                  AND ct.`category_id` = :category_id
            WHERE l.`is_active` = 1
        ');
        $stmtTotal->execute([':category_id' => $categoryId]);
        $total = (int) $stmtTotal->fetchColumn();

        // Filtered count
        $stmtFiltered = $this->prepareOrFail("
            SELECT COUNT(*)
            FROM `languages` l
            LEFT JOIN `maa_category_translations` ct
                   ON ct.`language_id` = l.`id`
                  AND ct.`category_id` = :category_id
            {$whereSql}
        ");
        $stmtFiltered->execute(array_merge([':category_id' => $categoryId], $params));
        $filtered = (int) $stmtFiltered->fetchColumn();

        // Data page
        $stmt = $this->prepareOrFail('
            SELECT ' . self::TRANSLATION_SELECT . "
            FROM `languages` l
            LEFT JOIN `maa_category_translations` ct
                   ON ct.`language_id` = l.`id`
                  AND ct.`category_id` = :category_id
            {$whereSql}
            ORDER BY l.`id` ASC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',       $limit,      PDO::PARAM_INT);
        $stmt->bindValue(':offset',      $offset,     PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<CategoryTranslationDTO> $data */
        $data = array_map(
            static fn (array $row): CategoryTranslationDTO => CategoryTranslationDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return [
            'data'       => $data,
            'pagination' => [
                'page'     => $page,
                'per_page' => $limit,
                'total'    => $total,
                'filtered' => $filtered,
            ],
        ];
    }

    // ================================================================== //
    //  Private — shared utilities
    // ================================================================== //

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw CategoryPersistenceException::prepareFailed($sql);
        }

        return $stmt;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchAssoc(PDOStatement $stmt): ?array
    {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAllAssoc(PDOStatement $stmt): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * @param list<int|string> $params
     */
    private function scalarInt(string $sql, array $params = []): int
    {
        $stmt = $this->prepareOrFail($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();

        return is_numeric($val) ? (int) $val : 0;
    }
}

