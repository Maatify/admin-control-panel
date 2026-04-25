<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\DeleteCategoryImageCommand;
use Maatify\Category\Command\DeleteCategorySettingCommand;
use Maatify\Category\Command\DeleteCategoryTranslationCommand;
use Maatify\Category\Command\UpdateCategoryCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpsertCategoryImageCommand;
use Maatify\Category\Command\UpsertCategorySettingCommand;
use Maatify\Category\Command\UpsertCategoryTranslationCommand;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryImageDTO;
use Maatify\Category\DTO\CategorySettingDTO;
use Maatify\Category\DTO\CategoryTranslationDTO;

/**
 * Write side — all mutations on categories and category_settings.
 * Each mutating method returns the freshly persisted DTO — no second round-trip needed.
 *
 * ── reorder parentId scoping ────────────────────────────────────────────
 *
 *  reorder() accepts ?parentId so the shift algorithm can be scoped
 *  to only the rows that live at the same hierarchy level as the
 *  moving row. Without this, shifting would bleed across levels.
 */
interface CategoryCommandRepositoryInterface
{
    // ================================================================== //
    //  Category CRUD
    // ================================================================== //

    public function create(CreateCategoryCommand $command): CategoryDTO;

    /**
     * Full update. If display_order changed, surrounding rows at the same
     * parent level are re-sorted atomically inside the same transaction.
     */
    public function update(UpdateCategoryCommand $command): CategoryDTO;

    public function updateStatus(UpdateCategoryStatusCommand $command): CategoryDTO;

    /**
     * Standalone position change — re-sorts all affected rows in one transaction.
     * parentId scopes the shift to only rows at the same hierarchy level.
     */
    public function reorder(int $id, int $newOrder, ?int $parentId): void;

    // ================================================================== //
    //  Settings CRUD
    // ================================================================== //

    /**
     * INSERT … ON DUPLICATE KEY UPDATE.
     * Safe to call whether or not the setting already exists.
     */
    public function upsertSetting(UpsertCategorySettingCommand $command): CategorySettingDTO;

    /**
     * Deletes the setting for (category_id, key).
     * Silent no-op if the row does not exist.
     */
    public function deleteSetting(DeleteCategorySettingCommand $command): void;

    // ================================================================== //
    //  Images CRUD
    // ================================================================== //

    /**
     * INSERT … ON DUPLICATE KEY UPDATE on (category_id, image_type, language_id).
     * Safe to call whether or not the slot already exists.
     *
     * @throws \Maatify\Category\Exception\CategoryInvalidArgumentException when language_id does not exist
     */
    public function upsertImage(UpsertCategoryImageCommand $command): CategoryImageDTO;

    /**
     * Deletes one (category_id, image_type, language_id) slot.
     * Silent no-op if the slot does not exist.
     */
    public function deleteImage(DeleteCategoryImageCommand $command): void;

    // ================================================================== //
    //  Translation CRUD
    // ================================================================== //

    /**
     * INSERT … ON DUPLICATE KEY UPDATE.
     * Safe to call whether or not a row already exists.
     *
     * @throws \Maatify\Category\Exception\CategoryInvalidArgumentException when language_id does not exist
     */
    public function upsertTranslation(UpsertCategoryTranslationCommand $command): CategoryTranslationDTO;

    /**
     * Deletes the translation for (category_id, language_id).
     * Silent no-op if the row does not exist.
     */
    public function deleteTranslation(DeleteCategoryTranslationCommand $command): void;
}

