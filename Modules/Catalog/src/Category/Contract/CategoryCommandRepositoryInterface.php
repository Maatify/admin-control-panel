<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Contract;

use DateTimeImmutable;
use Maatify\Catalog\Category\DTO\CreateCategoryDTO;
use Maatify\Catalog\Category\DTO\MoveCategoryDTO;
use Maatify\Catalog\Category\DTO\RestoreCategoryDTO;
use Maatify\Catalog\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryDisplayOrderDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryStatusDTO;

/**
 * Write port for Category persistence.
 *
 * The Catalog application owns mutation timestamps. Persistence adapters only
 * persist the timestamp supplied by the application and delegate display-order
 * mechanics to the approved persistence capability.
 */
interface CategoryCommandRepositoryInterface
{
    public function create(CreateCategoryDTO $command, DateTimeImmutable $occurredAt): int;

    public function move(MoveCategoryDTO $command, DateTimeImmutable $occurredAt): bool;

    public function softDelete(SoftDeleteCategoryDTO $command, DateTimeImmutable $occurredAt): bool;

    public function restore(RestoreCategoryDTO $command, DateTimeImmutable $occurredAt): bool;

    public function updateStatus(UpdateCategoryStatusDTO $command, DateTimeImmutable $occurredAt): bool;

    public function updateDisplayOrder(UpdateCategoryDisplayOrderDTO $command, DateTimeImmutable $occurredAt): bool;
}
