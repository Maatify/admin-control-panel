<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Rate\Service;

use Maatify\ExchangeRates\Admin\Rate\Command\CreateRateCommand;
use Maatify\ExchangeRates\Admin\Rate\Command\UpdateRateCommand;
use Maatify\ExchangeRates\Admin\Rate\Contract\RateCommandRepositoryInterface;
use Maatify\ExchangeRates\Admin\Rate\Contract\RateQueryRepositoryInterface;
use Maatify\ExchangeRates\Admin\Provider\Contract\ProviderQueryRepositoryInterface;
use Maatify\ExchangeRates\Exception\ExchangeRatesConflictException;
use Maatify\ExchangeRates\Exception\ExchangeRatesNotFoundException;

final class RateCommandService
{
    public function __construct(
        private readonly RateCommandRepositoryInterface    $commandRepo,
        private readonly RateQueryRepositoryInterface      $queryRepo,
        private readonly ProviderQueryRepositoryInterface  $providerQueryRepo,
    ) {}

    /**
     * Business guards before delegating to repo:
     *   1. Provider must exist and not be deleted
     *   2. Provider must be active
     *   3. Duplicate pair check is at DB level (UNIQUE KEY) → CodeAlreadyExistsException
     */
    public function create(CreateRateCommand $command): int
    {
        $provider = $this->providerQueryRepo->findById($command->providerId);

        if ($provider === null) {
            throw ExchangeRatesNotFoundException::withId($command->providerId);
        }
        if ($provider->deletedAt !== null) {
            throw ExchangeRatesConflictException::providerIsDeleted($command->providerId);
        }
        if (! $provider->isActive) {
            throw ExchangeRatesConflictException::providerIsInactive($command->providerId);
        }

        return $this->commandRepo->create($command);
    }

    public function updateRate(UpdateRateCommand $command): void
    {
        // Get current rate to check for no-op
        $raw = $this->queryRepo->findRawById($command->id);
        if ($raw === null) {
            throw ExchangeRatesNotFoundException::withId($command->id);
        }

        $currentRate = $raw['rate'] ?? null;
        if (is_string($currentRate) && bccomp($currentRate, $command->rate, 10) === 0) {
            throw ExchangeRatesConflictException::rateValueUnchanged($command->id);
        }

        $updated = $this->commandRepo->updateRate($command);
        if (! $updated) {
            throw ExchangeRatesNotFoundException::withId($command->id);
        }
    }

    public function updateStatus(int $id, bool $isActive): void
    {
        $updated = $this->commandRepo->updateStatus($id, $isActive);
        if (! $updated) {
            throw ExchangeRatesNotFoundException::withId($id);
        }
    }

    public function updateDisplayOrder(int $id, int $displayOrder): void
    {
        $this->commandRepo->updateDisplayOrder($id, $displayOrder);
    }

    public function softDelete(int $id): void
    {
        $deleted = $this->commandRepo->softDelete($id);
        if (! $deleted) {
            throw ExchangeRatesNotFoundException::withId($id);
        }
    }
}
