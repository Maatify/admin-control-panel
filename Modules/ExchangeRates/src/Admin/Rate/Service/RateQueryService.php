<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Rate\Service;

use Maatify\ExchangeRates\Admin\Rate\Contract\RateQueryRepositoryInterface;
use Maatify\ExchangeRates\Admin\Rate\DTO\RateDTO;
use Maatify\ExchangeRates\Exception\ExchangeRatesNotFoundException;

final class RateQueryService
{
    public function __construct(
        private readonly RateQueryRepositoryInterface $queryRepo,
    ) {}

    public function getById(int $id): RateDTO
    {
        $dto = $this->queryRepo->findById($id);
        if ($dto === null) {
            throw ExchangeRatesNotFoundException::withId($id);
        }
        return $dto;
    }

    /**
     * @param  array<string, string|int>  $columnFilters
     * @return array{data: list<\Maatify\ExchangeRates\Admin\Rate\DTO\RateListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function list(
        int     $page,
        int     $perPage,
        ?string $globalSearch = null,
        array   $columnFilters = [],
    ): array {
        return $this->queryRepo->list($page, $perPage, $globalSearch, $columnFilters);
    }
}
