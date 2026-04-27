<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

/**
 * Immutable value object carrying pagination metadata.
 *
 * Public readonly properties so json_encode() serialises it automatically
 * into the expected {"page":1,"per_page":20,"total":100,"filtered":50} shape.
 */
final readonly class PaginationInfo
{
    public function __construct(
        public int $page,
        public int $per_page,
        public int $total,
        public int $filtered,
    ) {}
}

