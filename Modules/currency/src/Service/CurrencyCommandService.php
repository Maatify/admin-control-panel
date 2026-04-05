<?php

declare(strict_types=1);

namespace Maatify\Currency\Service;

use Maatify\Currency\Command\CreateCurrencyCommand;
use Maatify\Currency\Command\DeleteCurrencyTranslationCommand;
use Maatify\Currency\Command\UpdateCurrencyCommand;
use Maatify\Currency\Command\UpdateCurrencyStatusCommand;
use Maatify\Currency\Command\UpsertCurrencyTranslationCommand;
use Maatify\Currency\Contract\CurrencyCommandRepositoryInterface;
use Maatify\Currency\Contract\CurrencyQueryReaderInterface;
use Maatify\Currency\DTO\CurrencyDTO;
use Maatify\Currency\DTO\CurrencyTranslationDTO;
use Maatify\Currency\Exception\CurrencyCodeAlreadyExistsException;
use Maatify\Currency\Exception\CurrencyNotFoundException;

/**
 * Write-side service — enforces all business rules before delegating
 * to the command repository.
 *
 * Controllers depend solely on this class and CurrencyQueryService.
 */
final class CurrencyCommandService
{
    public function __construct(
        private readonly CurrencyCommandRepositoryInterface $commandRepo,
        private readonly CurrencyQueryReaderInterface       $queryReader,
    ) {}

    // ================================================================== //
    //  Currency CRUD
    // ================================================================== //

    /**
     * @throws CurrencyCodeAlreadyExistsException
     */
    public function create(CreateCurrencyCommand $command): CurrencyDTO
    {
        $code = strtoupper($command->code);
        $this->assertCodeIsUnique($code, excludeId: null);

        // display_order = 0 means "append to end".
        // The repository handles auto-assignment atomically via an INSERT … SELECT
        // MAX subquery, so no race condition between reading MAX and inserting.
        return $this->commandRepo->create(new CreateCurrencyCommand(
            code:         $code,
            name:         $command->name,
            symbol:       $command->symbol,
            isActive:     $command->isActive,
            displayOrder: $command->displayOrder,
        ));
    }

    /**
     * @throws CurrencyNotFoundException
     * @throws CurrencyCodeAlreadyExistsException
     */
    public function update(UpdateCurrencyCommand $command): CurrencyDTO
    {
        $this->assertExists($command->id);

        $code = strtoupper($command->code);
        $this->assertCodeIsUnique($code, excludeId: $command->id);

        return $this->commandRepo->update(new UpdateCurrencyCommand(
            id:           $command->id,
            code:         $code,
            name:         $command->name,
            symbol:       $command->symbol,
            isActive:     $command->isActive,
            displayOrder: $command->displayOrder,
        ));
    }

    /**
     * @throws CurrencyNotFoundException
     */
    public function updateStatus(UpdateCurrencyStatusCommand $command): CurrencyDTO
    {
        $this->assertExists($command->id);

        return $this->commandRepo->updateStatus($command);
    }

    /**
     * @throws CurrencyNotFoundException
     * @throws \InvalidArgumentException  when $newOrder < 1
     */
    public function reorder(int $id, int $newOrder): void
    {
        if ($newOrder < 1) {
            throw new \InvalidArgumentException(
                sprintf('display_order must be >= 1, got %d.', $newOrder),
            );
        }

        $this->assertExists($id);

        $this->commandRepo->reorder($id, $newOrder);
    }

    // ================================================================== //
    //  Translation CRUD
    // ================================================================== //

    /**
     * Saves (creates or updates) the localised name for a currency.
     *
     * @throws CurrencyNotFoundException  when the currency does not exist
     */
    public function upsertTranslation(UpsertCurrencyTranslationCommand $command): CurrencyTranslationDTO
    {
        $this->assertExists($command->currencyId);

        return $this->commandRepo->upsertTranslation($command);
    }

    /**
     * Removes the localised name for a (currency, language) pair.
     * After deletion, queries automatically return the COALESCE base-name fallback.
     *
     * @throws CurrencyNotFoundException  when the currency does not exist
     */
    public function deleteTranslation(DeleteCurrencyTranslationCommand $command): void
    {
        $this->assertExists($command->currencyId);

        $this->commandRepo->deleteTranslation($command);
    }

    // ================================================================== //
    //  Private guards
    // ================================================================== //

    /**
     * @throws CurrencyNotFoundException
     */
    private function assertExists(int $id): void
    {
        if ($this->queryReader->findById($id) === null) {
            throw CurrencyNotFoundException::withId($id);
        }
    }

    /**
     * @throws CurrencyCodeAlreadyExistsException
     */
    private function assertCodeIsUnique(string $code, ?int $excludeId): void
    {
        $existing = $this->queryReader->findByCode($code);

        if ($existing === null) {
            return;
        }

        // Allow updating a row with its own existing code
        if ($excludeId !== null && $existing->id === $excludeId) {
            return;
        }

        throw CurrencyCodeAlreadyExistsException::withCode($code);
    }
}
