<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

use JsonSerializable;

/**
 * Typed paginated result — replaces the raw array{data, pagination} shape.
 *
 * Implements JsonSerializable so json_encode() produces:
 *   {"data": [...], "pagination": {"page":1,"per_page":20,"total":100,"filtered":50}}
 *
 * @template T of object
 */
final class PaginatedResult implements JsonSerializable
{
    /**
     * @param list<T> $data
     */
    public function __construct(
        public readonly array          $data,
        public readonly PaginationInfo $pagination,
    ) {}

    /**
     * @return array{data: list<T>, pagination: PaginationInfo}
     */
    public function jsonSerialize(): array
    {
        return [
            'data'       => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
