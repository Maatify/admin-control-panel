<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Contract;

use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\DTO\CategoryCollectionDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationCollectionDTO;

/** Read port consumed by Category business orchestration. */
interface CategoryQueryReaderInterface
{
    /** Finds a non-deleted, active Category whose complete ancestor chain is visible. */
    public function findVisibleById(int $categoryId): ?CategoryDTO;

    /** Lists visible root Categories in deterministic display order. */
    public function listVisibleRootCategories(): CategoryCollectionDTO;

    /**
     * Lists visible direct children of an active parent whose complete ancestor
     * chain is visible.
     */
    public function listVisibleChildren(int $parentId): CategoryCollectionDTO;

    /**
     * Lists non-deleted translations for a visible Category in language-code
     * order. Language validation and fallback remain Host responsibilities.
     */
    public function listVisibleTranslations(int $categoryId): CategoryTranslationCollectionDTO;

    /**
     * Finds an existing Category regardless of soft-delete state.
     *
     * This all-state lookup is used for stable-code reservation and restore.
     */
    public function findById(int $categoryId): ?CategoryDTO;

    /** Finds both active and soft-deleted rows so stable codes cannot be reused. */
    public function findByCode(string $code): ?CategoryDTO;

    /** Finds only a non-deleted Category for create and move validation. */
    public function findActiveById(int $categoryId): ?CategoryDTO;

    /**
     * Finds and locks an active Category row for the current transaction.
     *
     * Implementations MUST execute this lookup with a row lock and callers
     * MUST invoke it inside CategoryTransactionInterface::run().
     */
    public function findActiveByIdForUpdate(int $categoryId): ?CategoryDTO;

    /**
     * Finds and locks a Category row regardless of soft-delete state.
     *
     * This is the restore lookup and MUST execute with a row lock inside the
     * current CategoryTransactionInterface::run() transaction.
     */
    public function findByIdForUpdate(int $categoryId): ?CategoryDTO;

    /**
     * Returns whether a non-deleted child exists and locks matching child rows.
     *
     * The parent row MUST already be locked by findActiveByIdForUpdate() so a
     * concurrent child creation cannot pass the check between read and delete.
     */
    public function hasNonDeletedChildrenForUpdate(int $categoryId): bool;

    public function findTranslationById(int $translationId): ?CategoryTranslationDTO;
}
