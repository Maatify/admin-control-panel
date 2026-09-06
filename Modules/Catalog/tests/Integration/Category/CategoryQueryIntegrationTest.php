<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Integration\Category;

use DateTimeImmutable;
use Maatify\Catalog\Category\DTO\CreateCategoryDTO;
use Maatify\Catalog\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryStatusDTO;
use Maatify\Catalog\Category\Enum\CategoryStatusEnum;
use Maatify\Catalog\Category\Infrastructure\Repository\PdoCategoryCommandRepository;
use Maatify\Catalog\Category\Infrastructure\Repository\PdoCategoryQueryReader;
use Maatify\Catalog\Category\Infrastructure\Repository\PdoCategoryTranslationCommandRepository;
use Maatify\Catalog\Category\Infrastructure\Transaction\PdoCategoryTransaction;
use Maatify\Catalog\Category\Service\CategoryCommandService;
use Maatify\Catalog\Category\Service\CategoryQueryService;
use Maatify\Catalog\Tests\Integration\Support\CatalogMySqlIntegrationTestCase;
use Maatify\Catalog\Tests\Integration\Support\FixedCatalogClock;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;

final class CategoryQueryIntegrationTest extends CatalogMySqlIntegrationTestCase
{
    public function testVisibleIdentityAndRootListExcludeDeletedOrInactiveRowsAndUseDisplayOrderThenId(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $firstId = $commandService->create(new CreateCategoryDTO('root-first'));
        $secondId = $commandService->create(new CreateCategoryDTO('root-second'));
        $thirdId = $commandService->create(new CreateCategoryDTO('root-third'));
        $inactiveId = $commandService->create(new CreateCategoryDTO('root-inactive'));
        $deletedId = $commandService->create(new CreateCategoryDTO('root-deleted'));

        $commandService->updateStatus(new UpdateCategoryStatusDTO($inactiveId, CategoryStatusEnum::INACTIVE));
        $commandService->softDelete(new SoftDeleteCategoryDTO($deletedId));
        $this->setDisplayOrder($connection, $firstId, 2);
        $this->setDisplayOrder($connection, $secondId, 1);
        $this->setDisplayOrder($connection, $thirdId, 1);
        $this->setDisplayOrder($connection, $inactiveId, 0);
        $this->setDisplayOrder($connection, $deletedId, 0);

        $reader = new PdoCategoryQueryReader($connection);
        $queryService = new CategoryQueryService($reader);

        self::assertSame($secondId, $queryService->getById($secondId)->id);
        self::assertNull($reader->findVisibleById($inactiveId));
        self::assertNull($reader->findVisibleById($deletedId));
        self::assertSame([$secondId, $thirdId, $firstId], $this->categoryIds($queryService->listRootCategories()));
    }

    public function testChildrenAreOrderedAndHiddenWhenAnyAncestorIsInactive(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $activeRootId = $commandService->create(new CreateCategoryDTO('active-root'));
        $inactiveRootId = $commandService->create(new CreateCategoryDTO('inactive-root'));
        $firstChildId = $commandService->create(new CreateCategoryDTO('first-child', $activeRootId));
        $secondChildId = $commandService->create(new CreateCategoryDTO('second-child', $activeRootId));
        $inactiveChildId = $commandService->create(new CreateCategoryDTO('inactive-child', $activeRootId));
        $deletedChildId = $commandService->create(new CreateCategoryDTO('deleted-child', $activeRootId));
        $grandchildId = $commandService->create(new CreateCategoryDTO('grandchild', $firstChildId));

        $commandService->updateStatus(new UpdateCategoryStatusDTO($inactiveRootId, CategoryStatusEnum::INACTIVE));
        $commandService->updateStatus(new UpdateCategoryStatusDTO($inactiveChildId, CategoryStatusEnum::INACTIVE));
        $commandService->softDelete(new SoftDeleteCategoryDTO($deletedChildId));
        $this->setDisplayOrder($connection, $firstChildId, 1);
        $this->setDisplayOrder($connection, $secondChildId, 1);
        $this->setDisplayOrder($connection, $inactiveChildId, 0);
        $this->setDisplayOrder($connection, $deletedChildId, 0);

        $reader = new PdoCategoryQueryReader($connection);
        $queryService = new CategoryQueryService($reader);

        self::assertSame(
            [$firstChildId, $secondChildId],
            $this->categoryIds($queryService->listChildren($activeRootId)),
        );
        self::assertSame([], $this->categoryIds($queryService->listChildren($inactiveRootId)));
        self::assertSame($grandchildId, $queryService->getById($grandchildId)->id);

        $commandService->updateStatus(new UpdateCategoryStatusDTO($activeRootId, CategoryStatusEnum::INACTIVE));

        self::assertSame([], $this->categoryIds($queryService->listChildren($activeRootId)));
        self::assertNull($reader->findVisibleById($grandchildId));
    }

    public function testTranslationsExcludeSoftDeletedRowsAndInvisibleCategoryPaths(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $visibleId = $commandService->create(new CreateCategoryDTO('translated-category'));
        $inactiveId = $commandService->create(new CreateCategoryDTO('translated-inactive'));
        $deletedId = $commandService->create(new CreateCategoryDTO('translated-deleted'));
        $commandService->updateStatus(new UpdateCategoryStatusDTO($inactiveId, CategoryStatusEnum::INACTIVE));
        $commandService->softDelete(new SoftDeleteCategoryDTO($deletedId));

        $this->insertTranslation($connection, $visibleId, 'en-US', 'Shirts', null, null);
        $this->insertTranslation($connection, $visibleId, 'ar-EG', 'قمصان', 'وصف', null);
        $this->insertTranslation($connection, $visibleId, 'fr-FR', 'Chemises', null, '2026-01-02 00:00:00');
        $this->insertTranslation($connection, $inactiveId, 'en-US', 'Inactive', null, null);
        $this->insertTranslation($connection, $deletedId, 'en-US', 'Deleted', null, null);

        $queryService = new CategoryQueryService(new PdoCategoryQueryReader($connection));
        $visibleTranslations = $queryService->listTranslations($visibleId);
        $languages = [];
        foreach ($visibleTranslations as $translation) {
            $languages[] = $translation->languageCode;
        }

        self::assertSame(['ar-EG', 'en-US'], $languages);
        self::assertTrue($queryService->listTranslations($inactiveId)->isEmpty());
        self::assertTrue($queryService->listTranslations($deletedId)->isEmpty());
    }

    /** @return list<int> */
    private function categoryIds(\Maatify\Catalog\Category\DTO\CategoryCollectionDTO $categories): array
    {
        $ids = [];
        foreach ($categories as $category) {
            $ids[] = $category->id;
        }

        return $ids;
    }

    private function commandService(PDO $connection): CategoryCommandService
    {
        return new CategoryCommandService(
            new PdoCategoryCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryQueryReader($connection),
            new PdoCategoryTranslationCommandRepository($connection),
            new PdoCategoryTransaction($connection),
            new FixedCatalogClock('2026-01-01 00:00:00 UTC'),
        );
    }

    private function setDisplayOrder(PDO $connection, int $categoryId, int $displayOrder): void
    {
        $statement = $connection->prepare(
            'UPDATE `maa_catalog_categories` '
            . 'SET `display_order` = :display_order WHERE `id` = :category_id',
        );
        $statement->execute([
            'display_order' => $displayOrder,
            'category_id' => $categoryId,
        ]);
    }

    private function insertTranslation(
        PDO $connection,
        int $categoryId,
        string $languageCode,
        string $name,
        ?string $description,
        ?string $deletedAt,
    ): void {
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 UTC');
        $statement = $connection->prepare(
            'INSERT INTO `maa_catalog_category_translations` '
            . '(`category_id`, `language_code`, `name`, `description`, '
            . '`created_at`, `updated_at`, `deleted_at`) '
            . 'VALUES (:category_id, :language_code, :name, :description, '
            . ':created_at, :updated_at, :deleted_at)',
        );
        $statement->execute([
            'category_id' => $categoryId,
            'language_code' => $languageCode,
            'name' => $name,
            'description' => $description,
            'created_at' => $timestamp->format('Y-m-d H:i:s'),
            'updated_at' => $timestamp->format('Y-m-d H:i:s'),
            'deleted_at' => $deletedAt,
        ]);
    }
}
