<?php

declare(strict_types=1);

namespace Maatify\Category\Infrastructure\Repository;

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
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;
use PDOStatement;
use Throwable;

final readonly class PdoCategoryCommandRepository implements CategoryCommandRepositoryInterface
{
    public function __construct(
        private PDO                          $pdo,
        private CategoryQueryReaderInterface $queryReader,
        private ScopedOrderingManager        $orderingManager = new ScopedOrderingManager(),
    ) {
    }

    // ================================================================== //
    //  CREATE
    // ================================================================== //

    /** {@inheritDoc} */
    public function create(CreateCategoryCommand $command): CategoryDTO
    {
        $displayOrder = $command->displayOrder === 0
            ? $this->orderingManager->getNextPosition($this->pdo, $this->orderingConfig(), $command->parentId)
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
    //  UPDATE
    // ================================================================== //

    /** {@inheritDoc} */
    public function update(UpdateCategoryCommand $command): CategoryDTO
    {
        // Reorder siblings atomically before writing other fields.
        // ScopedOrderingManager::moveWithinScope() reads the current position
        // from the DB inside its own transaction, so no pre-fetch is needed.
        $this->orderingManager->moveWithinScope(
            $this->pdo,
            $this->orderingConfig(),
            $command->parentId,
            $command->id,
            $command->displayOrder,
        );

        try {
            $stmt = $this->prepareOrFail('
                UPDATE `maa_categories`
                SET `parent_id`     = :parent_id,
                    `name`          = :name,
                    `slug`          = :slug,
                    `description`   = :description,
                    `is_active`     = :is_active,
                    `notes`         = :notes
                WHERE `id` = :id
            ');
            $stmt->execute([
                ':parent_id'   => $command->parentId,
                ':name'        => $command->name,
                ':slug'        => $command->slug,
                ':description' => $command->description,
                ':is_active'   => $command->isActive ? 1 : 0,
                ':notes'       => $command->notes,
                ':id'          => $command->id,
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
            $moved = $this->orderingManager->moveWithinScope(
                $this->pdo,
                $this->orderingConfig(),
                $parentId,
                $id,
                $newOrder,
            );

            if (!$moved) {
                throw CategoryNotFoundException::withId($id);
            }
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
    }

    // ================================================================== //
    //  Private — ordering config
    // ================================================================== //

    private function orderingConfig(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(
            table:          'maa_categories',
            scopeColumn:    'parent_id',
            idColumn:       'id',
            orderColumn:    'display_order',
            deletedAtColumn: null,
        );
    }

    // ================================================================== //
    //  Private — DB helpers
    // ================================================================== //

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
        $enum = CategoryImageTypeEnum::fromString($imageType);
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

