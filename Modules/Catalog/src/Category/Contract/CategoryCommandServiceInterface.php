<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Contract;

use Maatify\Catalog\Category\DTO\CreateCategoryDTO;
use Maatify\Catalog\Category\DTO\MoveCategoryDTO;
use Maatify\Catalog\Category\DTO\RestoreCategoryDTO;
use Maatify\Catalog\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryDisplayOrderDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryStatusDTO;
use Maatify\Catalog\Category\DTO\UpdateCategoryTranslationDTO;

/** Public application contract for Category business mutations. */
interface CategoryCommandServiceInterface
{
    public function create(CreateCategoryDTO $command): int;

    public function move(MoveCategoryDTO $command): void;

    public function softDelete(SoftDeleteCategoryDTO $command): void;

    public function restore(RestoreCategoryDTO $command): void;

    public function updateStatus(UpdateCategoryStatusDTO $command): void;

    public function updateDisplayOrder(UpdateCategoryDisplayOrderDTO $command): void;

    public function updateTranslation(UpdateCategoryTranslationDTO $command): void;
}
