<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Service;

use Maatify\Catalog\Category\Contract\CategoryCommandRepositoryInterface;
use Maatify\Catalog\Category\Contract\CategoryCommandServiceInterface;
use Maatify\Catalog\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Catalog\Category\Contract\CategoryTranslationCommandRepositoryInterface;
use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\DTO\CreateCategoryDTO;
use Maatify\Catalog\Category\DTO\MoveCategoryDTO;
use Maatify\Catalog\Category\DTO\RestoreCategoryDTO;
use Maatify\Catalog\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryDisplayOrderDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryStatusDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryTranslationDTO;
use Maatify\Catalog\Category\Exception\CategoryCodeAlreadyExistsException;
use Maatify\Catalog\Category\Exception\CategoryCycleException;
use Maatify\Catalog\Category\Exception\CategoryHasNonDeletedChildrenException;
use Maatify\Catalog\Category\Exception\CategoryNotFoundException;
use Maatify\Catalog\Category\Exception\CategoryTranslationNotFoundException;

/** Coordinates Category business rules without owning persistence or SQL. */
final readonly class CategoryCommandService implements CategoryCommandServiceInterface
{
    public function __construct(
        private CategoryCommandRepositoryInterface $commandRepository,
        private CategoryQueryReaderInterface $queryReader,
        private CategoryTranslationCommandRepositoryInterface $translationCommandRepository,
    ) {}

    public function create(CreateCategoryDTO $command): int
    {
        if ($this->queryReader->findByCode($command->code) !== null) {
            throw CategoryCodeAlreadyExistsException::withCode($command->code);
        }

        if ($command->parentId !== null) {
            $this->requireCategory($command->parentId);
        }

        return $this->commandRepository->create($command);
    }

    public function move(MoveCategoryDTO $command): void
    {
        $category = $this->requireCategory($command->categoryId);

        if ($command->parentId !== null) {
            $this->requireCategory($command->parentId);
            $this->assertMoveDoesNotCreateCycle($category->id, $command->parentId);
        }

        if (!$this->commandRepository->move($command)) {
            throw CategoryNotFoundException::withId($command->categoryId);
        }
    }

    public function softDelete(SoftDeleteCategoryDTO $command): void
    {
        $this->requireCategory($command->categoryId);

        if ($this->queryReader->hasNonDeletedChildren($command->categoryId)) {
            throw CategoryHasNonDeletedChildrenException::withId($command->categoryId);
        }

        if (!$this->commandRepository->softDelete($command)) {
            throw CategoryNotFoundException::withId($command->categoryId);
        }
    }

    public function restore(RestoreCategoryDTO $command): void
    {
        $this->requireCategory($command->categoryId);

        if (!$this->commandRepository->restore($command)) {
            throw CategoryNotFoundException::withId($command->categoryId);
        }
    }

    public function updateStatus(UpdateCategoryStatusDTO $command): void
    {
        $this->requireCategory($command->categoryId);

        if (!$this->commandRepository->updateStatus($command)) {
            throw CategoryNotFoundException::withId($command->categoryId);
        }
    }

    public function updateDisplayOrder(UpdateCategoryDisplayOrderDTO $command): void
    {
        $this->requireCategory($command->categoryId);

        if (!$this->commandRepository->updateDisplayOrder($command)) {
            throw CategoryNotFoundException::withId($command->categoryId);
        }
    }

    public function updateTranslation(UpdateCategoryTranslationDTO $command): void
    {
        if ($this->queryReader->findTranslationById($command->translationId) === null) {
            throw CategoryTranslationNotFoundException::withId($command->translationId);
        }

        if (!$this->translationCommandRepository->update($command)) {
            throw CategoryTranslationNotFoundException::withId($command->translationId);
        }
    }

    private function requireCategory(int $categoryId): CategoryDTO
    {
        $category = $this->queryReader->findById($categoryId);

        if ($category === null) {
            throw CategoryNotFoundException::withId($categoryId);
        }

        return $category;
    }

    private function assertMoveDoesNotCreateCycle(int $categoryId, int $newParentId): void
    {
        /** @var array<int, true> $visited */
        $visited = [$categoryId => true];
        $currentId = $newParentId;

        while (true) {
            if (isset($visited[$currentId])) {
                throw CategoryCycleException::forMove($categoryId, $newParentId);
            }

            $visited[$currentId] = true;
            $parent = $this->requireCategory($currentId);
            if ($parent->parentId === null) {
                return;
            }

            $currentId = $parent->parentId;
        }
    }
}
