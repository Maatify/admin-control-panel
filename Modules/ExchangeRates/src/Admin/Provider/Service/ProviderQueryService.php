<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Provider\Service;

use Maatify\ExchangeRates\Admin\Provider\Contract\ProviderQueryRepositoryInterface;
use Maatify\ExchangeRates\Admin\Provider\DTO\ProviderDTO;
use Maatify\ExchangeRates\Exception\ExchangeRatesNotFoundException;

final class ProviderQueryService
{
    public function __construct(
        private readonly ProviderQueryRepositoryInterface $queryRepo,
    ) {}

    /**
     * @throws ExchangeRatesNotFoundException
     */
    public function getById(int $id): ProviderDTO
    {
        $dto = $this->queryRepo->findById($id);
        if ($dto === null) {
            throw ExchangeRatesNotFoundException::withId($id);
        }
        return $dto;
    }

    /**
     * @throws ExchangeRatesNotFoundException
     */
    public function getByCode(string $code): ProviderDTO
    {
        $dto = $this->queryRepo->findByCode($code);
        if ($dto === null) {
            throw ExchangeRatesNotFoundException::withCode($code);
        }
        return $dto;
    }

    /**
     * @param  array<string, string|int>  $columnFilters
     * @return array{data: list<\Maatify\ExchangeRates\Admin\Provider\DTO\ProviderListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
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
