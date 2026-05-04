<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Provider\Service;

use Maatify\ExchangeRates\Admin\Provider\Command\CreateProviderCommand;
use Maatify\ExchangeRates\Admin\Provider\Command\UpdateProviderCommand;
use Maatify\ExchangeRates\Admin\Provider\Contract\ProviderCommandRepositoryInterface;
use Maatify\ExchangeRates\Exception\ExchangeRatesNotFoundException;

final class ProviderCommandService
{
    public function __construct(
        private readonly ProviderCommandRepositoryInterface $commandRepo,
    ) {}

    public function create(CreateProviderCommand $command): int
    {
        return $this->commandRepo->create($command);
    }

    public function update(UpdateProviderCommand $command): void
    {
        if (! $this->commandRepo->update($command)) {
            throw ExchangeRatesNotFoundException::withId($command->id);
        }
    }

    public function updateStatus(int $id, bool $isActive): void
    {
        if (! $this->commandRepo->updateStatus($id, $isActive)) {
            throw ExchangeRatesNotFoundException::withId($id);
        }
    }

    public function updateDisplayOrder(int $id, int $displayOrder): void
    {
        $this->commandRepo->updateDisplayOrder($id, $displayOrder);
    }

    public function softDelete(int $id): void
    {
        if (! $this->commandRepo->softDelete($id)) {
            throw ExchangeRatesNotFoundException::withId($id);
        }
    }
}
