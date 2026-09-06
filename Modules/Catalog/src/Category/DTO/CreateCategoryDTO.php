<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\DTO;

use Maatify\Catalog\Category\Enum\CategoryStatusEnum;
use Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException;

/**
 * Validated input for creating a Category.
 *
 * Display order is intentionally absent; creation-time ordering belongs to
 * the persistence adapter and the shared ordering contract.
 */
final readonly class CreateCategoryDTO
{
    public string $code;
    public ?int $parentId;
    public CategoryStatusEnum $status;

    public function __construct(
        string $code,
        int|string|null $parentId = null,
        CategoryStatusEnum $status = CategoryStatusEnum::ACTIVE,
    ) {
        if (trim($code) === '') {
            throw CategoryInvalidArgumentException::emptyField('code');
        }

        if (mb_strlen($code) > 100) {
            throw CategoryInvalidArgumentException::fieldTooLong('code', 100);
        }

        $this->code = $code;
        $this->parentId = $parentId === null
            ? null
            : (new CategoryIdDTO($parentId, 'parentId'))->value;
        $this->status = $status;
    }
}
