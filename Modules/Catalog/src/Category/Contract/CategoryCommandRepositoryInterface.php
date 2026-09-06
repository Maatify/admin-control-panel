<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Contract;

use Maatify\Catalog\Category\DTO\CreateCategoryDTO;
use Maatify\Catalog\Category\DTO\MoveCategoryDTO;
use Maatify\Catalog\Category\DTO\RestoreCategoryDTO;
use Maatify\Catalog\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryDisplayOrderDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryStatusDTO;

/**
 * Write port for Category persistence.
 *
 * Implementations own application timestamps and must delegate persisted
 * display-order mechanics to the approved persistence capability.
 */
interface CategoryCommandRepositoryInterface
{
    public function create(CreateCategoryDTO $command): int;

    public function move(MoveCategoryDTO $command): bool;

    public function softDelete(SoftDeleteCategoryDTO $command): bool;

    public function restore(RestoreCategoryDTO $command): bool;

    public function updateStatus(UpdateCategoryStatusDTO $command): bool;

    public function updateDisplayOrder(UpdateCategoryDisplayOrderDTO $command): bool;
}
