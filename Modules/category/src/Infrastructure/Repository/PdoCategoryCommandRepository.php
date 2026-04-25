<?php

declare(strict_types=1);

namespace Maatify\Category\Infrastructure\Repository;

use Maatify\AdminKernel\Infrastructure\Persistence\Support\ScopedOrderingManager;
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
use Maatify\Category\Exception\CategoryExceptionInterface;
use Maatify\Category\Exception\CategoryImageNotFoundException;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Category\Exception\CategorySettingNotFoundException;
use Maatify\Category\Exception\CategorySlugAlreadyExistsException;
use Maatify\Category\Exception\CategoryTranslationNotFoundException;
use Maatify\Category\Enum\CategoryImageTypeEnum;
use PDO;
use PDOStatement;
use Throwable;

final class PdoCategoryCommandRepository implements CategoryCommandRepositoryInterface
{
    private ScopedOrderingManager $ordering;

    public function __construct(
        private readonly PDO                          $pdo,
        private readonly CategoryQueryReaderInterface $queryReader,
    ) {
        $this->ordering = new ScopedOrderingManager($pdo);
    }

    // ================================================================== //
    //  CREATE
    // ================================================================== //

    /** {@inheritDoc} */
    public function create(CreateCategoryCommand $command): CategoryDTO
    {
        $scope = ['parent_id' => $command->parentId];

        $displayOrder = $command->displayOrder === 0
            ? $this->ordering->getNextPosition('maa_categories', 'display_order', $scope)
            : $command->displayOrder;

        $stmt = $this->prepareOrFail('
            INSERT INTO `maa_categories` (`parent_id`, `name`, `slug`, `description`, `is_active`, `display_order`, `notes`)
            VALUES (:parent_id, :name, :slug, :description, :is_active, :display_order, :notes)
        ');

        try {
            $stmt->execute([
                ':parent_id'     => $command->parentId,
                ':name'          => $command->name,
                ':slug'          => $command->slug,
                ':description'   => $command->description,
                ':is_active'     => $command->isActive ? 1 : 0,
                ':display_order' => $displayOrder,
                ':notes'         => $command->notes,
            ]);
        } catch (\PDOException $e) {
            if ($this->isDuplicateKeyError($e)) {
                throw CategorySlugAlreadyExistsException::withSlug($command->slug);
            }
            throw CategoryPersistenceException::fromPdoException($e);
        }

        return $this->fetchDtoOrFail((int) $this->pdo->lastInsertId());
    }

    // ================================================================== //
    //  UPDATE (full replace + atomic re-sort)
    // ================================================================== //

    /** {@inheritDoc} */
    public function update(UpdateCategoryCommand $command): CategoryDTO
    {
        $scope    = ['parent_id' => $command->parentId];
        $oldOrder = $this->fetchDisplayOrder($command->id);

        if ($oldOrder !== $command->displayOrder) {
            $this->ordering->moveWithinScope(
                table:            'maa_categories',
                idColumn:         'id',
                idValue:          $command->id,
                currentPosition:  $oldOrder,
                requestedPosition: $command->displayOrder,
                orderColumn:      'display_order',
                scope:            $scope,
            );
        }

        try {
            $stmt = $this->prepareOrFail('
                UPDATE `maa_categories`
                SET `parent_id`     = :parent_id,
                    `name`          = :name,
                    `slug`          = :slug,
                    `description`   = :description,
                    `is_active`     = :is_active,
                    `display_order` = :display_order,
                    `notes`         = :notes
                WHERE `id` = :id
            ');
            $stmt->execute([
                ':parent_id'     => $command->parentId,
                ':name'          => $command->name,
                ':slug'          => $command->slug,
                ':description'   => $command->description,
                ':is_active'     => $command->isActive ? 1 : 0,
                ':display_order' => $command->displayOrder,
                ':notes'         => $command->notes,
                ':id'            => $command->id,
            ]);
        } catch (\PDOException $e) {
            if ($this->isDuplicateKeyError($e)) {
                throw CategorySlugAlreadyExistsException::withSlug($command->slug);
            }
            throw CategoryPersistenceException::fromPdoException($e);
        }

        return $this->fetchDtoOrFail($command->id);
    }

    // ================================================================== //
    //  UPDATE STATUS
    // ================================================================== //

    /** {@inheritDoc} */
    public function updateStatus(UpdateCategoryStatusCommand $command): CategoryDTO
    {
        $stmt = $this->prepareOrFail(
            'UPDATE `maa_categories` SET `is_active` = :is_active WHERE `id` = :id',
        );
        $stmt->execute([
            ':is_active' => $command->isActive ? 1 : 0,
            ':id'        => $command->id,
        ]);

        return $this->fetchDtoOrFail($command->id);
    }

    // ================================================================== //
    //  STANDALONE RE-ORDER
    // ================================================================== //

    /** {@inheritDoc} */
    public function reorder(int $id, int $newOrder, ?int $parentId): void
    {
        try {
            // Pre-flight read — optimistic equality check to avoid an unnecessary
            // transaction when the position has not changed.
            // ⚠️ This read is NOT inside a transaction; it is intentionally
            // without FOR UPDATE because the lock would be released immediately
            // in autocommit mode and provide no real concurrency protection.
            // moveWithinScope() owns its own atomic transaction for the actual update.
            $oldOrder = $this->fetchDisplayOrder($id);

            if ($oldOrder === $newOrder) {
                return;
            }

            // ScopedOrderingManager::moveWithinScope() handles its own transaction
            // and writes the final display_order back to the item row.
            $this->ordering->moveWithinScope(
                table:             'maa_categories',
                idColumn:          'id',
                idValue:           $id,
                currentPosition:   $oldOrder,
                requestedPosition: $newOrder,
                orderColumn:       'display_order',
                scope:             ['parent_id' => $parentId],
            );
        } catch (Throwable $e) {
            if ($e instanceof CategoryExceptionInterface) {
                throw $e;
            }
            throw CategoryPersistenceException::fromThrowable($e);
        }
    }

    // ================================================================== //
    //  SETTINGS — UPSERT
    // ================================================================== //

    /** {@inheritDoc} */
    public function upsertSetting(UpsertCategorySettingCommand $command): CategorySettingDTO
    {
        $stmt = $this->prepareOrFail('
            INSERT INTO `maa_category_settings` (`category_id`, `key`, `value`)
            VALUES (:category_id, :key, :insert_value)
            ON DUPLICATE KEY UPDATE `value` = :update_value
        ');

        try {
            $stmt->execute([
                ':category_id'   => $command->categoryId,
                ':key'           => $command->key,
                ':insert_value'  => $command->value,
                ':update_value'  => $command->value,
            ]);
        } catch (\PDOException $e) {
            throw CategoryPersistenceException::fromPdoException($e);
        }

        return $this->fetchSettingOrFail($command->categoryId, $command->key);
    }

    // ================================================================== //
    //  SETTINGS — DELETE
    // ================================================================== //

    /** {@inheritDoc} */
    public function deleteSetting(DeleteCategorySettingCommand $command): void
    {
        $stmt = $this->prepareOrFail(
            'DELETE FROM `maa_category_settings` WHERE `category_id` = ? AND `key` = ?',
        );
        $stmt->execute([$command->categoryId, $command->key]);
        // Silent no-op when row does not exist (rowCount = 0 is acceptable).
    }

    // ================================================================== //
    //  IMAGES — UPSERT
    // ================================================================== //

    /** {@inheritDoc} */
    public function upsertImage(UpsertCategoryImageCommand $command): CategoryImageDTO
    {
        $stmt = $this->prepareOrFail('
            INSERT INTO `maa_category_images` (`category_id`, `image_type`, `language_id`, `path`)
            VALUES (:category_id, :image_type, :language_id, :insert_path)
            ON DUPLICATE KEY UPDATE `path` = :update_path
        ');

        try {
            $stmt->execute([
                ':category_id'  => $command->categoryId,
                ':image_type'   => $command->imageType->value,
                ':language_id'  => $command->languageId,
                ':insert_path'  => $command->path,
                ':update_path'  => $command->path,
            ]);
        } catch (\PDOException $e) {
            // MySQL 1452: FK violation on language_id — language does not exist.
            // category_id FK is already guarded by CategoryCommandService::assertExists().
            if ($this->isForeignKeyViolation($e)) {
                throw CategoryInvalidArgumentException::invalidLanguageId($command->languageId);
            }
            throw CategoryPersistenceException::fromPdoException($e);
        }

        return $this->fetchImageOrFail($command->categoryId, $command->imageType->value, $command->languageId);
    }

    // ================================================================== //
    //  IMAGES — DELETE
    // ================================================================== //

    /** {@inheritDoc} */
    public function deleteImage(DeleteCategoryImageCommand $command): void
    {
        $stmt = $this->prepareOrFail(
            'DELETE FROM `maa_category_images` WHERE `category_id` = ? AND `image_type` = ? AND `language_id` = ?',
        );
        $stmt->execute([$command->categoryId, $command->imageType->value, $command->languageId]);
        // Silent no-op when slot does not exist.
    }

    // ================================================================== //
    //  Translation CRUD
    // ================================================================== //

    /** {@inheritDoc} */
    public function upsertTranslation(UpsertCategoryTranslationCommand $command): CategoryTranslationDTO
    {
        $stmt = $this->prepareOrFail(
            'INSERT INTO `maa_category_translations` (`category_id`, `language_id`, `name`, `description`)
                VALUES (:category_id, :language_id, :insert_name, :insert_desc)
                ON DUPLICATE KEY UPDATE `name` = :update_name, `description` = :update_desc',
        );

        try {
            $stmt->execute([
                ':category_id'  => $command->categoryId,
                ':language_id'  => $command->languageId,
                ':insert_name'  => $command->translatedName,
                ':insert_desc'  => $command->translatedDescription,
                ':update_name'  => $command->translatedName,
                ':update_desc'  => $command->translatedDescription,
            ]);
        } catch (\PDOException $e) {
            if ($this->isForeignKeyViolation($e)) {
                throw CategoryInvalidArgumentException::invalidLanguageId($command->languageId);
            }
            throw CategoryPersistenceException::fromPdoException($e);
        }

        return $this->fetchTranslationOrFail($command->categoryId, $command->languageId);
    }

    /** {@inheritDoc} */
    public function deleteTranslation(DeleteCategoryTranslationCommand $command): void
    {
        $stmt = $this->prepareOrFail(
            'DELETE FROM `maa_category_translations` WHERE `category_id` = ? AND `language_id` = ?',
        );
        $stmt->execute([$command->categoryId, $command->languageId]);
        // Silent no-op if the row does not exist.
    }


    // ================================================================== //
    //  Private — DB helpers
    // ================================================================== //

    private function fetchDisplayOrder(int $id): int
    {
        $stmt = $this->prepareOrFail(
            'SELECT `display_order` FROM `maa_categories` WHERE `id` = ? LIMIT 1',
        );
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) {
            throw CategoryNotFoundException::withId($id);
        }

        /** @var array<string, mixed> $row */
        $order = $row['display_order'];

        if (is_int($order)) {
            return $order;
        }

        if (is_numeric($order)) {
            return (int) $order;
        }

        throw CategoryPersistenceException::unexpectedColumnType('display_order', $id);
    }

    private function fetchDtoOrFail(int $id): CategoryDTO
    {
        $dto = $this->queryReader->findById($id);
        if ($dto === null) {
            throw CategoryNotFoundException::withId($id);
        }

        return $dto;
    }

    private function fetchSettingOrFail(int $categoryId, string $key): CategorySettingDTO
    {
        $dto = $this->queryReader->findSetting($categoryId, $key);
        if ($dto === null) {
            throw CategorySettingNotFoundException::for($categoryId, $key);
        }

        return $dto;
    }

    private function fetchImageOrFail(int $categoryId, string $imageType, int $languageId): CategoryImageDTO
    {
        $enum = CategoryImageTypeEnum::from($imageType);
        $dto  = $this->queryReader->findImage($categoryId, $enum, $languageId);
        if ($dto === null) {
            throw CategoryImageNotFoundException::for($categoryId, $imageType, $languageId);
        }

        return $dto;
    }

    private function fetchTranslationOrFail(int $categoryId, int $languageId): CategoryTranslationDTO
    {
        $dto = $this->queryReader->findTranslation($categoryId, $languageId);
        if ($dto === null) {
            throw CategoryTranslationNotFoundException::for($categoryId, $languageId);
        }

        return $dto;
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw CategoryPersistenceException::prepareFailed($sql);
        }

        return $stmt;
    }

    private function isDuplicateKeyError(\PDOException $e): bool
    {
        return $e->getCode() === '23000'
               && str_contains($e->getMessage(), '1062');
    }

    private function isForeignKeyViolation(\PDOException $e): bool
    {
        return $e->getCode() === '23000'
               && str_contains($e->getMessage(), '1452');
    }
}

