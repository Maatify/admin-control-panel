<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Category\Service;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Maatify\Catalog\Category\Contract\CategoryTransactionInterface;
use Maatify\Catalog\Category\Contract\CategoryCommandRepositoryInterface;
use Maatify\Catalog\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Catalog\Category\Contract\CategoryTranslationCommandRepositoryInterface;
use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationDTO;
use Maatify\Catalog\Category\DTO\CreateCategoryDTO;
use Maatify\Catalog\Category\DTO\MoveCategoryDTO;
use Maatify\Catalog\Category\DTO\RestoreCategoryDTO;
use Maatify\Catalog\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryDisplayOrderDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryStatusDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryTranslationDTO;
use Maatify\Catalog\Category\Enum\CategoryStatusEnum;
use Maatify\Catalog\Category\Exception\CategoryCodeAlreadyExistsException;
use Maatify\Catalog\Category\Exception\CategoryCycleException;
use Maatify\Catalog\Category\Exception\CategoryHasNonDeletedChildrenException;
use Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Catalog\Category\Service\CategoryCommandService;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PHPUnit\Framework\TestCase;
use Throwable;

final class CategoryCommandServiceTest extends TestCase
{
    public function testCreateValidatesParentAndKeepsCodeOutsideMutationContracts(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([$this->category(7, null)]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        $createdId = $service->create(new CreateCategoryDTO('shirts', '7'));

        self::assertSame(99, $createdId);
        $created = $commandRepository->created;
        self::assertNotNull($created);
        self::assertSame('shirts', $created->code);
        self::assertSame(7, $created->parentId);
        self::assertSame(1, $transaction->runs);
        self::assertSame([7], $queryReader->lockedIds);
        self::assertSame('2026-01-03 00:00:00', $commandRepository->occurredAt?->format('Y-m-d H:i:s'));
        self::assertFalse(property_exists(UpdateCategoryStatusDTO::class, 'code'));
        self::assertFalse(property_exists(UpdateCategoryDisplayOrderDTO::class, 'code'));
    }

    public function testCreateRejectsAStableCodeThatAlreadyExistsIncludingSoftDeletedRows(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([$this->category(7, null, $this->deletedAt())]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $service = $this->service($commandRepository, $queryReader);

        $this->expectException(CategoryCodeAlreadyExistsException::class);
        $service->create(new CreateCategoryDTO('category-7'));
    }

    public function testMoveToAValidParentIsDelegated(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([
            $this->category(1, null),
            $this->category(2, null),
            $this->category(3, 1),
        ]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        $service->move(new MoveCategoryDTO(3, 2));

        $moved = $commandRepository->moved;
        self::assertNotNull($moved);
        self::assertSame(3, $moved->categoryId);
        self::assertSame(2, $moved->parentId);
        self::assertSame(1, $transaction->runs);
        self::assertSame([3, 2], $queryReader->lockedIds);
        self::assertSame('2026-01-03 00:00:00', $commandRepository->occurredAt?->format('Y-m-d H:i:s'));
    }

    public function testDirectSelfParentIsRejectedByTheInputDTO(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new MoveCategoryDTO('7', '7');
    }

    public function testIndirectCycleIsRejectedAcrossTheWholeAncestorChain(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([
            $this->category(1, null),
            $this->category(2, 1),
            $this->category(3, 2),
        ]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        try {
            $service->move(new MoveCategoryDTO(1, 3));
            self::fail('The complete ancestor chain must reject an indirect cycle.');
        } catch (CategoryCycleException) {
            self::assertSame(1, $transaction->runs);
            self::assertSame(1, $transaction->rollbacks);
            self::assertSame([1, 3, 2], $queryReader->lockedIds);
        }
    }

    public function testSoftDeleteRejectsANonDeletedChild(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([
            $this->category(1, null),
            $this->category(2, 1),
        ]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        try {
            $service->softDelete(new SoftDeleteCategoryDTO(1));
            self::fail('A Category with a non-deleted child must not be soft-deleted.');
        } catch (CategoryHasNonDeletedChildrenException) {
            self::assertSame(1, $transaction->runs);
            self::assertSame(1, $transaction->rollbacks);
            self::assertNull($commandRepository->softDeleted);
        }
    }

    public function testSoftDeleteIsAllowedWhenAllChildrenAreDeleted(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([
            $this->category(1, null),
            $this->category(2, 1, $this->deletedAt()),
        ]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        $service->softDelete(new SoftDeleteCategoryDTO('1'));

        $deleted = $commandRepository->softDeleted;
        self::assertNotNull($deleted);
        self::assertSame(1, $deleted->categoryId);
        self::assertSame(1, $transaction->runs);
    }

    public function testRestoreUsesTheExistingCategoryIdentity(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([$this->category(11, null, $this->deletedAt())]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        $service->restore(new RestoreCategoryDTO('11'));

        $restored = $commandRepository->restored;
        self::assertNotNull($restored);
        self::assertSame(11, $restored->categoryId);
        self::assertSame(1, $transaction->runs);
    }

    public function testStatusAndDisplayOrderMutationsAreDedicatedOperations(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([$this->category(5, null)]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $service = $this->service($commandRepository, $queryReader);

        $service->updateStatus(new UpdateCategoryStatusDTO(5, CategoryStatusEnum::INACTIVE));
        $service->updateDisplayOrder(new UpdateCategoryDisplayOrderDTO(5, 3));

        self::assertSame(CategoryStatusEnum::INACTIVE, $commandRepository->statusUpdated?->status);
        self::assertSame(3, $commandRepository->displayOrderUpdated?->displayOrder);
    }

    public function testTranslationMutationCannotChangeItsLogicalIdentity(): void
    {
        $translation = new CategoryTranslationDTO(
            id: 21,
            categoryId: 5,
            languageCode: 'en-US',
            name: 'Shirts',
            description: null,
            createdAt: $this->createdAt(),
            updatedAt: $this->createdAt(),
            deletedAt: null,
        );
        $queryReader = new InMemoryCategoryQueryReader([$this->category(5, null)], [$translation]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $translationRepository = new InMemoryCategoryTranslationCommandRepository();
        $service = new CategoryCommandService(
            $commandRepository,
            $queryReader,
            $translationRepository,
            new InMemoryCategoryTransaction(),
            new FixedClock(),
        );

        $service->updateTranslation(new UpdateCategoryTranslationDTO(21, 'قمصان', 'وصف'));

        $updated = $translationRepository->updated;
        self::assertNotNull($updated);
        self::assertSame(21, $updated->translationId);
        self::assertFalse(property_exists(UpdateCategoryTranslationDTO::class, 'categoryId'));
        self::assertFalse(property_exists(UpdateCategoryTranslationDTO::class, 'languageCode'));
    }

    private function service(
        InMemoryCategoryCommandRepository $commandRepository,
        InMemoryCategoryQueryReader $queryReader,
        ?InMemoryCategoryTransaction $transaction = null,
    ): CategoryCommandService {
        return new CategoryCommandService(
            $commandRepository,
            $queryReader,
            new InMemoryCategoryTranslationCommandRepository(),
            $transaction ?? new InMemoryCategoryTransaction(),
            new FixedClock(),
        );
    }

    private function category(int $id, ?int $parentId, ?DateTimeImmutable $deletedAt = null): CategoryDTO
    {
        return new CategoryDTO(
            id: $id,
            parentId: $parentId,
            code: 'category-' . $id,
            status: CategoryStatusEnum::ACTIVE,
            displayOrder: 1,
            createdAt: $this->createdAt(),
            updatedAt: $this->createdAt(),
            deletedAt: $deletedAt,
        );
    }

    private function createdAt(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-01 00:00:00 UTC');
    }

    private function deletedAt(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-02 00:00:00 UTC');
    }
}

/** @internal Test-only in-memory query port. */
final class InMemoryCategoryQueryReader implements CategoryQueryReaderInterface
{
    /** @var list<int> */
    public array $lockedIds = [];

    /** @var list<CategoryDTO> */
    private array $categories;

    /** @var list<CategoryTranslationDTO> */
    private array $translations;

    /**
     * @param list<CategoryDTO>            $categories
     * @param list<CategoryTranslationDTO> $translations
     */
    public function __construct(array $categories, array $translations = [])
    {
        $this->categories = $categories;
        $this->translations = $translations;
    }

    public function findById(int $categoryId): ?CategoryDTO
    {
        foreach ($this->categories as $category) {
            if ($category->id === $categoryId) {
                return $category;
            }
        }

        return null;
    }

    public function findByCode(string $code): ?CategoryDTO
    {
        foreach ($this->categories as $category) {
            if ($category->code === $code) {
                return $category;
            }
        }

        return null;
    }

    public function findActiveById(int $categoryId): ?CategoryDTO
    {
        foreach ($this->categories as $category) {
            if ($category->id === $categoryId && $category->deletedAt === null) {
                return $category;
            }
        }

        return null;
    }

    public function findActiveByIdForUpdate(int $categoryId): ?CategoryDTO
    {
        $this->lockedIds[] = $categoryId;

        return $this->findActiveById($categoryId);
    }

    public function findByIdForUpdate(int $categoryId): ?CategoryDTO
    {
        $this->lockedIds[] = $categoryId;

        return $this->findById($categoryId);
    }

    public function hasNonDeletedChildrenForUpdate(int $categoryId): bool
    {
        foreach ($this->categories as $category) {
            if ($category->parentId === $categoryId && $category->deletedAt === null) {
                return true;
            }
        }

        return false;
    }

    public function findTranslationById(int $translationId): ?CategoryTranslationDTO
    {
        foreach ($this->translations as $translation) {
            if ($translation->id === $translationId) {
                return $translation;
            }
        }

        return null;
    }
}

/** @internal Test-only in-memory command port. */
final class InMemoryCategoryCommandRepository implements CategoryCommandRepositoryInterface
{
    public ?DateTimeImmutable $occurredAt = null;

    public ?CreateCategoryDTO $created = null;
    public ?MoveCategoryDTO $moved = null;
    public ?SoftDeleteCategoryDTO $softDeleted = null;
    public ?RestoreCategoryDTO $restored = null;
    public ?UpdateCategoryStatusDTO $statusUpdated = null;
    public ?UpdateCategoryDisplayOrderDTO $displayOrderUpdated = null;

    public function create(CreateCategoryDTO $command, DateTimeImmutable $occurredAt): int
    {
        $this->created = $command;
        $this->occurredAt = $occurredAt;

        return 99;
    }

    public function move(MoveCategoryDTO $command, DateTimeImmutable $occurredAt): bool
    {
        $this->moved = $command;
        $this->occurredAt = $occurredAt;

        return true;
    }

    public function softDelete(SoftDeleteCategoryDTO $command, DateTimeImmutable $occurredAt): bool
    {
        $this->softDeleted = $command;
        $this->occurredAt = $occurredAt;

        return true;
    }

    public function restore(RestoreCategoryDTO $command, DateTimeImmutable $occurredAt): bool
    {
        $this->restored = $command;
        $this->occurredAt = $occurredAt;

        return true;
    }

    public function updateStatus(UpdateCategoryStatusDTO $command, DateTimeImmutable $occurredAt): bool
    {
        $this->statusUpdated = $command;
        $this->occurredAt = $occurredAt;

        return true;
    }

    public function updateDisplayOrder(UpdateCategoryDisplayOrderDTO $command, DateTimeImmutable $occurredAt): bool
    {
        $this->displayOrderUpdated = $command;
        $this->occurredAt = $occurredAt;

        return true;
    }
}

/** @internal Test-only in-memory translation command port. */
final class InMemoryCategoryTranslationCommandRepository implements CategoryTranslationCommandRepositoryInterface
{
    public ?UpdateCategoryTranslationDTO $updated = null;

    public function update(UpdateCategoryTranslationDTO $command, DateTimeImmutable $occurredAt): bool
    {
        $this->updated = $command;

        return true;
    }
}

/** @internal Test-only transaction port. */
final class InMemoryCategoryTransaction implements CategoryTransactionInterface
{
    public int $runs = 0;
    public int $commits = 0;
    public int $rollbacks = 0;

    public function run(Closure $operation): mixed
    {
        $this->runs++;

        try {
            $result = $operation();
            $this->commits++;

            return $result;
        } catch (Throwable $exception) {
            $this->rollbacks++;

            throw $exception;
        }
    }
}

/** @internal Test-only deterministic application clock. */
final class FixedClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct()
    {
        $this->now = new DateTimeImmutable('2026-01-03 00:00:00 UTC');
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->now->getTimezone();
    }
}
