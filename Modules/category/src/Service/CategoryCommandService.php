<?php

declare(strict_types=1);

namespace Maatify\Category\Service;

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\DeleteCategoryImageCommand;
use Maatify\Category\Command\DeleteCategorySettingCommand;
use Maatify\Category\Command\DeleteCategoryTranslationCommand;
use Maatify\Category\Command\UpdateCategoryCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpsertCategoryImageCommand;
use Maatify\Category\Command\UpsertCategorySettingCommand;
use Maatify\Category\Command\UpsertCategoryTranslationCommand;
use Maatify\Category\Contract\CategoryCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryImageDTO;
use Maatify\Category\DTO\CategorySettingDTO;
use Maatify\Category\DTO\CategoryTranslationDTO;
use Maatify\Category\Exception\CategoryCircularReferenceException;
use Maatify\Category\Exception\CategoryDepthExceededException;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategorySlugAlreadyExistsException;

/**
 * Write-side service — enforces ALL business rules before delegating
 * to the command repository.
 *
 * Controllers depend solely on this class and CategoryQueryService.
 *
 * ── Business rules enforced here ────────────────────────────────────────
 *
 *  1. Slug uniqueness (TOCTOU guard — DB UNIQUE KEY is the final safety net)
 *  2. Max depth = 1: a sub-category cannot have children
 *  3. No circular references: a category cannot become its own ancestor
 *  4. Parent must exist before being assigned
 *  5. display_order must be >= 1 in reorder()
 */
final class CategoryCommandService
{
    public function __construct(
        private readonly CategoryCommandRepositoryInterface $commandRepo,
        private readonly CategoryQueryReaderInterface       $queryReader,
    ) {}

    // ================================================================== //
    //  Category CRUD
    // ================================================================== //

    /**
     * @throws CategorySlugAlreadyExistsException
     * @throws CategoryNotFoundException          when parentId does not exist
     * @throws CategoryDepthExceededException     when parentId is itself a sub-category
     */
    public function create(CreateCategoryCommand $command): CategoryDTO
    {
        $this->assertSlugIsUnique($command->slug, excludeId: null);

        if ($command->parentId !== null) {
            $this->assertParentExistsAndIsRoot($command->parentId);
        }

        return $this->commandRepo->create($command);
    }

    /**
     * @throws CategoryNotFoundException          when id or parentId does not exist
     * @throws CategorySlugAlreadyExistsException
     * @throws CategoryDepthExceededException     when new parentId is itself a sub-category
     * @throws CategoryCircularReferenceException when parentId equals the category's own id
     */
    public function update(UpdateCategoryCommand $command): CategoryDTO
    {
        $this->assertExists($command->id);
        $this->assertSlugIsUnique($command->slug, excludeId: $command->id);

        if ($command->parentId !== null) {
            $this->assertParentExistsAndIsRoot($command->parentId);
            $this->assertNotCircularReference($command->id, $command->parentId);
        }

        return $this->commandRepo->update($command);
    }

    /**
     * @throws CategoryNotFoundException
     */
    public function updateStatus(UpdateCategoryStatusCommand $command): CategoryDTO
    {
        $this->assertExists($command->id);

        return $this->commandRepo->updateStatus($command);
    }

    /**
     * @throws CategoryNotFoundException
     * @throws CategoryInvalidArgumentException when $newOrder < 1
     */
    public function reorder(int $id, int $newOrder, ?int $parentId): void
    {
        if ($newOrder < 1) {
            throw CategoryInvalidArgumentException::invalidDisplayOrder($newOrder);
        }

        $this->assertExists($id);

        $this->commandRepo->reorder($id, $newOrder, $parentId);
    }

    // ================================================================== //
    //  Settings CRUD
    // ================================================================== //

    /**
     * @throws CategoryNotFoundException
     */
    public function upsertSetting(UpsertCategorySettingCommand $command): CategorySettingDTO
    {
        $this->assertExists($command->categoryId);

        return $this->commandRepo->upsertSetting($command);
    }

    /**
     * @throws CategoryNotFoundException
     */
    public function deleteSetting(DeleteCategorySettingCommand $command): void
    {
        $this->assertExists($command->categoryId);

        $this->commandRepo->deleteSetting($command);
    }

    // ================================================================== //
    //  Images CRUD
    // ================================================================== //

    /**
     * @throws CategoryNotFoundException
     * @throws \Maatify\Category\Exception\CategoryInvalidArgumentException when language_id does not exist
     */
    public function upsertImage(UpsertCategoryImageCommand $command): CategoryImageDTO
    {
        $this->assertExists($command->categoryId);

        return $this->commandRepo->upsertImage($command);
    }

    /**
     * @throws CategoryNotFoundException
     */
    public function deleteImage(DeleteCategoryImageCommand $command): void
    {
        $this->assertExists($command->categoryId);

        $this->commandRepo->deleteImage($command);
    }

    // ================================================================== //
    //  Translation CRUD
    // ================================================================== //

    /**
     * Saves (creates or updates) the localised name for a category.
     *
     * @throws CategoryNotFoundException when the category does not exist
     */
    public function upsertTranslation(UpsertCategoryTranslationCommand $command): CategoryTranslationDTO
    {
        $this->assertExists($command->categoryId);

        return $this->commandRepo->upsertTranslation($command);
    }

    /**
     * Removes the localised name for a (category, language) pair.
     *
     * @throws CategoryNotFoundException when the category does not exist
     */
    public function deleteTranslation(DeleteCategoryTranslationCommand $command): void
    {
        $this->assertExists($command->categoryId);

        $this->commandRepo->deleteTranslation($command);
    }

    // ================================================================== //
    //  Private guards
    // ================================================================== //

    /**
     * @throws CategoryNotFoundException
     */
    private function assertExists(int $id): void
    {
        if ($this->queryReader->findById($id) === null) {
            throw CategoryNotFoundException::withId($id);
        }
    }

    /**
     * @throws CategorySlugAlreadyExistsException
     */
    private function assertSlugIsUnique(string $slug, ?int $excludeId): void
    {
        $existing = $this->queryReader->findBySlug($slug);

        if ($existing === null) {
            return;
        }

        // Allow updating a row with its own existing slug.
        if ($excludeId !== null && $existing->id === $excludeId) {
            return;
        }

        throw CategorySlugAlreadyExistsException::withSlug($slug);
    }

    /**
     * Asserts the given parentId exists AND is itself a root category.
     * A sub-category cannot be a parent — that would exceed the max depth of 1.
     *
     * @throws CategoryNotFoundException      when parent does not exist
     * @throws CategoryDepthExceededException when parent is a sub-category
     */
    private function assertParentExistsAndIsRoot(int $parentId): void
    {
        $parent = $this->queryReader->findById($parentId);

        if ($parent === null) {
            throw CategoryNotFoundException::withId($parentId);
        }

        // The parent must be a root (depth 0) for the new child to be at depth 1.
        // If the parent already has a parent_id, it is a sub-category (depth 1),
        // and adding a child would create depth 2 — which is not allowed.
        if ($parent->parentId !== null) {
            throw CategoryDepthExceededException::parentIsAlreadySubCategory($parentId);
        }
    }

    /**
     * Asserts that assigning $targetParentId as the parent of $categoryId
     * would not create a circular reference.
     *
     * With max depth = 1, the only possible circular case is a category
     * being assigned as its own parent. Deeper cycles cannot occur because
     * assertParentExistsAndIsRoot() already guarantees the parent is a root.
     *
     * @throws CategoryCircularReferenceException
     */
    private function assertNotCircularReference(int $categoryId, int $targetParentId): void
    {
        if ($categoryId === $targetParentId) {
            throw CategoryCircularReferenceException::detected($categoryId, $targetParentId);
        }
    }
}

