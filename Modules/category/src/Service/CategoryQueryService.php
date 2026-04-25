<?php

declare(strict_types=1);

namespace Maatify\Category\Service;

use Maatify\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryImageDTO;
use Maatify\Category\DTO\CategorySettingDTO;
use Maatify\Category\DTO\CategoryTranslationDTO;
use Maatify\Category\Enum\CategoryImageTypeEnum;
use Maatify\Category\Exception\CategoryNotFoundException;

/**
 * Read-side service.
 *
 * Thin delegation layer over CategoryQueryReaderInterface.
 * Adds one responsibility: converts null returns into typed exceptions
 * where the caller expects a guaranteed result.
 *
 * Controllers and other services depend on this class, never on the
 * query reader directly.
 */
final class CategoryQueryService
{
    public function __construct(
        private readonly CategoryQueryReaderInterface $reader,
    ) {}

    // ------------------------------------------------------------------ //
    //  Admin — paginated
    // ------------------------------------------------------------------ //

    /**
     * @param  array<string, int|string> $columnFilters
     * @return array{
     *     data:       list<CategoryDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function paginate(
        int     $page          = 1,
        int     $perPage       = 20,
        ?string $globalSearch  = null,
        array   $columnFilters = [],
    ): array {
        return $this->reader->listCategories($page, $perPage, $globalSearch, $columnFilters);
    }

    /**
     * @param  array<string, int|string> $columnFilters
     * @return array{
     *     data:       list<CategoryDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function paginateSubCategories(
        int     $parentId,
        int     $page          = 1,
        int     $perPage       = 20,
        ?string $globalSearch  = null,
        array   $columnFilters = [],
    ): array {
        return $this->reader->listSubCategories($parentId, $page, $perPage, $globalSearch, $columnFilters);
    }

    // ------------------------------------------------------------------ //
    //  Website — active only, no pagination
    // ------------------------------------------------------------------ //

    /** @return list<CategoryDTO> */
    public function activeRootList(): array
    {
        return $this->reader->listActiveRootCategories();
    }

    /** @return list<CategoryDTO> */
    public function activeSubList(int $parentId): array
    {
        return $this->reader->listActiveSubCategories($parentId);
    }

    // ------------------------------------------------------------------ //
    //  Single-record
    // ------------------------------------------------------------------ //

    /**
     * @throws CategoryNotFoundException
     */
    public function getById(int $id): CategoryDTO
    {
        $dto = $this->reader->findById($id);
        if ($dto === null) {
            throw CategoryNotFoundException::withId($id);
        }

        return $dto;
    }

    /**
     * @throws CategoryNotFoundException
     */
    public function getBySlug(string $slug): CategoryDTO
    {
        $dto = $this->reader->findBySlug($slug);
        if ($dto === null) {
            throw CategoryNotFoundException::withSlug($slug);
        }

        return $dto;
    }

    // ------------------------------------------------------------------ //
    //  Settings
    // ------------------------------------------------------------------ //

    public function findSetting(int $categoryId, string $key): ?CategorySettingDTO
    {
        return $this->reader->findSetting($categoryId, $key);
    }

    /**
     * @param  array<string, int|string> $columnFilters
     * @return array{
     *     data:       list<CategorySettingDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function listSettingsPaginated(
        int     $categoryId,
        int     $page          = 1,
        int     $perPage       = 20,
        ?string $globalSearch  = null,
        array   $columnFilters = [],
    ): array {
        return $this->reader->listSettings($categoryId, $page, $perPage, $globalSearch, $columnFilters);
    }

    // ------------------------------------------------------------------ //
    //  Images
    // ------------------------------------------------------------------ //

    /**
     * @return array<string, list<CategoryImageDTO>>
     */
    public function listImages(int $categoryId): array
    {
        return $this->reader->listImages($categoryId);
    }

    public function findImage(int $categoryId, CategoryImageTypeEnum $imageType, int $languageId): ?CategoryImageDTO
    {
        return $this->reader->findImage($categoryId, $imageType, $languageId);
    }

    // ------------------------------------------------------------------ //
    //  Translations
    // ------------------------------------------------------------------ //

    public function findTranslation(int $categoryId, int $languageId): ?CategoryTranslationDTO
    {
        return $this->reader->findTranslation($categoryId, $languageId);
    }

    /**
     * @param  array<string, int|string> $columnFilters
     * @return array{
     *     data:       list<CategoryTranslationDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function listTranslationsPaginated(
        int     $categoryId,
        int     $page          = 1,
        int     $perPage       = 20,
        ?string $globalSearch  = null,
        array   $columnFilters = [],
    ): array {
        return $this->reader->listTranslationsForCategoryPaginated(
            $categoryId,
            $page,
            $perPage,
            $globalSearch,
            $columnFilters,
        );
    }
}

