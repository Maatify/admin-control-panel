<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryImageDTO;
use Maatify\Category\DTO\CategorySettingDTO;
use Maatify\Category\DTO\CategoryTranslationDTO;
use Maatify\Category\Enum\CategoryImageTypeEnum;

/**
 * Read side — all queries on categories and category_settings.
 *
 * ── Hierarchy behaviour ─────────────────────────────────────────────────
 *
 *  listCategories()
 *      Returns ALL categories (root + sub) with pagination.
 *      Use columnFilters['parent_id'] to scope:
 *        - key absent     → no parent filter (all levels)
 *        - value = 0      → root categories only (WHERE parent_id IS NULL)
 *        - value = int    → sub-categories of that parent
 *
 *  listSubCategories(parentId)
 *      Dedicated query: always scoped to WHERE parent_id = $parentId.
 *      Used for the sub-categories admin screen and dropdown.
 *
 *  listActiveRootCategories() / listActiveSubCategories(parentId)
 *      No pagination — for website dropdowns and UI selectors.
 *
 * ── childCount ──────────────────────────────────────────────────────────
 *
 *  findById() and listCategories() include a computed child_count field
 *  in the returned DTO. Sub-category queries always return childCount=0.
 *
 * ── displayOrder scoping ────────────────────────────────────────────────
 *
 *  maxDisplayOrder(?parentId):
 *    parentId = null → MAX WHERE parent_id IS NULL  (root level)
 *    parentId = int  → MAX WHERE parent_id = ?      (sub-category level)
 */
interface CategoryQueryReaderInterface
{
    // ================================================================== //
    //  Admin list — paginated, searchable, filterable
    // ================================================================== //

    /**
     * @param  array<string, int|string> $columnFilters
     *   Allowed keys: is_active (0|1), parent_id (int, 0=roots only), name (LIKE), slug (LIKE)
     * @return array{
     *     data:       list<CategoryDTO>,
     *     pagination: array{
     *         page:     int,
     *         per_page: int,
     *         total:    int,
     *         filtered: int
     *     }
     * }
     */
    public function listCategories(
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array;

    /**
     * @param  array<string, int|string> $columnFilters
     *   Allowed keys: is_active (0|1), name (LIKE), slug (LIKE)
     * @return array{
     *     data:       list<CategoryDTO>,
     *     pagination: array{
     *         page:     int,
     *         per_page: int,
     *         total:    int,
     *         filtered: int
     *     }
     * }
     */
    public function listSubCategories(
        int     $parentId,
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array;

    // ================================================================== //
    //  Website list — active only, no pagination
    // ================================================================== //

    /** @return list<CategoryDTO> */
    public function listActiveRootCategories(): array;

    /** @return list<CategoryDTO> */
    public function listActiveSubCategories(int $parentId): array;

    // ================================================================== //
    //  Single-record lookups
    // ================================================================== //

    public function findById(int $id): ?CategoryDTO;

    public function findBySlug(string $slug): ?CategoryDTO;

    // ================================================================== //
    //  Aggregates needed by the write side
    // ================================================================== //

    /**
     * Returns the current max display_order at a given hierarchy level.
     * Used by the repository to compute auto display_order on create.
     *
     * parentId = null → scope is root level (WHERE parent_id IS NULL)
     * parentId = int  → scope is sub-category level (WHERE parent_id = ?)
     */
    public function maxDisplayOrder(?int $parentId): int;

    // ================================================================== //
    //  Settings
    // ================================================================== //

    /**
     * @param  array<string, int|string> $columnFilters
     *   Allowed keys: key (LIKE), value (LIKE)
     * @return array{
     *     data:       list<CategorySettingDTO>,
     *     pagination: array{
     *         page:     int,
     *         per_page: int,
     *         total:    int,
     *         filtered: int
     *     }
     * }
     */
    public function listSettings(
        int     $categoryId,
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array;

    public function findSetting(int $categoryId, string $key): ?CategorySettingDTO;

    // ================================================================== //
    //  Images
    // ================================================================== //

    /**
     * Returns all images for a category grouped by image type.
     * Every type key is always present even when empty.
     *
     * @return array<string, list<CategoryImageDTO>>
     *   Keys: 'image', 'mobile_image', 'api_image', 'website_image'
     */
    public function listImages(int $categoryId): array;

    public function findImage(int $categoryId, CategoryImageTypeEnum $imageType, int $languageId): ?CategoryImageDTO;

    // ================================================================== //
    //  Translations
    // ================================================================== //

    /**
     * Returns the translation row enriched with language identity, or null
     * if no translation exists for the given (category_id, language_id) pair.
     *
     * Uses INNER JOIN — returned DTO has no null translation fields.
     */
    public function findTranslation(int $categoryId, int $languageId): ?CategoryTranslationDTO;

    /**
     * Returns ALL active languages LEFT JOINed with category_translations.
     * Languages without a translation row have $dto->translatedName === null.
     *
     * @param  array<string, int|string> $columnFilters
     *   Allowed keys: language_id, language_code, language_name, name, has_translation
     * @return array{
     *     data:       list<CategoryTranslationDTO>,
     *     pagination: array{
     *         page:     int,
     *         per_page: int,
     *         total:    int,
     *         filtered: int
     *     }
     * }
     */
    public function listTranslationsForCategoryPaginated(
        int     $categoryId,
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array;
}

