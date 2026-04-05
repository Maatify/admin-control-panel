<?php

declare(strict_types=1);

namespace Maatify\Currency\Contract;

use Maatify\Currency\Command\CreateCurrencyCommand;
use Maatify\Currency\Command\DeleteCurrencyTranslationCommand;
use Maatify\Currency\Command\UpdateCurrencyCommand;
use Maatify\Currency\Command\UpdateCurrencyStatusCommand;
use Maatify\Currency\Command\UpsertCurrencyTranslationCommand;
use Maatify\Currency\DTO\CurrencyDTO;
use Maatify\Currency\DTO\CurrencyTranslationDTO;

/**
 * Write side — all mutations on currencies + currency_translations.
 * Each mutating method returns the freshly persisted DTO — no second round-trip needed.
 */
interface CurrencyCommandRepositoryInterface
{
    // ================================================================== //
    //  Currency CRUD
    // ================================================================== //

    public function create(CreateCurrencyCommand $command): CurrencyDTO;

    /**
     * Full update. If display_order changed, surrounding rows are re-sorted
     * atomically inside the same transaction.
     */
    public function update(UpdateCurrencyCommand $command): CurrencyDTO;

    public function updateStatus(UpdateCurrencyStatusCommand $command): CurrencyDTO;

    /**
     * Standalone position change — re-sorts all affected rows in one transaction.
     */
    public function reorder(int $id, int $newOrder): void;

    // ================================================================== //
    //  Translation CRUD
    // ================================================================== //

    /**
     * INSERT … ON DUPLICATE KEY UPDATE.
     * Safe to call whether or not a row already exists.
     */
    public function upsertTranslation(UpsertCurrencyTranslationCommand $command): CurrencyTranslationDTO;

    /**
     * Deletes the translation for (currency_id, language_id).
     * Silent no-op if the row does not exist.
     * After deletion, queries return the COALESCE base-name fallback automatically.
     */
    public function deleteTranslation(DeleteCurrencyTranslationCommand $command): void;
}
