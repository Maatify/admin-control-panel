<?php

declare(strict_types=1);

namespace Maatify\Currency\Infrastructure\Repository;

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
use Maatify\Currency\Exception\CurrencyExceptionInterface;
use Maatify\Currency\Exception\CurrencyInvalidArgumentException;
use Maatify\Currency\Exception\CurrencyNotFoundException;
use Maatify\Currency\Exception\CurrencyPersistenceException;
use Maatify\Currency\Exception\CurrencyTranslationNotFoundException;
use PDO;
use PDOStatement;
use Throwable;

final class PdoCurrencyCommandRepository implements CurrencyCommandRepositoryInterface
{
    /**
     * @param CurrencyQueryReaderInterface $queryReader  Injected for translation read-back
     *                                                   after upsert — avoids duplicating
     *                                                   the JOIN SELECT from the query reader.
     */
    public function __construct(
        private readonly PDO                         $pdo,
        private readonly CurrencyQueryReaderInterface $queryReader,
    ) {}

    /** {@inheritDoc} */
    public function create(CreateCurrencyCommand $command): CurrencyDTO
    {
        if ($command->displayOrder === 0) {
            // Atomic auto-order: MAX inside the INSERT avoids a separate
            // SELECT MAX + INSERT pair, but does NOT prevent concurrent
            // inserts from receiving the same display_order (since display_order
            // has no UNIQUE constraint). Ties are acceptable — reorder() fixes them.
            $stmt = $this->prepareOrFail(
                'INSERT INTO `currencies`
                     (`code`, `name`, `symbol`, `is_active`, `display_order`)
                 SELECT :code, :name, :symbol, :is_active,
                        COALESCE(MAX(`display_order`), 0) + 1
                 FROM   `currencies`',
            );
        } else {
            $stmt = $this->prepareOrFail(
                'INSERT INTO `currencies`
                     (`code`, `name`, `symbol`, `is_active`, `display_order`)
                 VALUES
                     (:code, :name, :symbol, :is_active, :display_order)',
            );
        }

        // strtoupper is intentionally repeated here — the repository must not
        // assume callers always go through CurrencyCommandService.
        $params = [
            ':code'      => strtoupper($command->code),
            ':name'      => $command->name,
            ':symbol'    => $command->symbol,
            ':is_active' => $command->isActive ? 1 : 0,
        ];

        if ($command->displayOrder !== 0) {
            $params[':display_order'] = $command->displayOrder;
        }

        try {
            $stmt->execute($params);
        } catch (\PDOException $e) {
            // MySQL error 1062: Duplicate entry — the UNIQUE KEY on `code` fired.
            // The service-level assertCodeIsUnique() has a TOCTOU race under
            // concurrent requests; the DB constraint is the last line of defence.
            if ($this->isDuplicateKeyError($e)) {
                throw CurrencyCodeAlreadyExistsException::withCode($command->code);
            }
            throw CurrencyPersistenceException::fromPdoException($e);
        }

        return $this->fetchDtoOrFail((int) $this->pdo->lastInsertId());
    }

    // ================================================================== //
    //  UPDATE (full replace + atomic re-sort)
    // ================================================================== //

    /**
     * {@inheritDoc}
     *
     * Opens the transaction BEFORE reading oldOrder so that the SELECT and all
     * subsequent writes are atomic — prevents a race where another process changes
     * display_order between our read and our UPDATE.
     */
    public function update(UpdateCurrencyCommand $command): CurrencyDTO
    {
        // Open transaction FIRST so that the read of oldOrder and the subsequent
        // writes are atomic — prevents a race where another process changes
        // display_order between our read and our UPDATE.
        $this->pdo->beginTransaction();
        try {
            $oldOrder = $this->fetchDisplayOrder($command->id);

            if ($oldOrder !== $command->displayOrder) {
                $this->shiftRows($command->id, $oldOrder, $command->displayOrder);
            }

            $stmt = $this->prepareOrFail(
                'UPDATE `currencies`
                 SET `code`          = :code,
                     `name`          = :name,
                     `symbol`        = :symbol,
                     `is_active`     = :is_active,
                     `display_order` = :display_order
                 WHERE `id` = :id',
            );
            $stmt->execute([
                ':code'          => strtoupper($command->code),
                ':name'          => $command->name,
                ':symbol'        => $command->symbol,
                ':is_active'     => $command->isActive ? 1 : 0,
                ':display_order' => $command->displayOrder,
                ':id'            => $command->id,
            ]);

            $this->pdo->commit();
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            if ($this->isDuplicateKeyError($e)) {
                throw CurrencyCodeAlreadyExistsException::withCode($command->code);
            }
            throw CurrencyPersistenceException::fromPdoException($e);
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            // Re-throw currency exceptions as-is; they carry the correct domain semantics.
            // Wrap anything unexpected (e.g. a driver-level error) as a persistence failure.
            if ($e instanceof CurrencyExceptionInterface) {
                throw $e;
            }
            throw CurrencyPersistenceException::fromThrowable($e);
        }

        return $this->fetchDtoOrFail($command->id);
    }

    // ================================================================== //
    //  UPDATE STATUS
    // ================================================================== //

    /** {@inheritDoc} */
    public function updateStatus(UpdateCurrencyStatusCommand $command): CurrencyDTO
    {
        // Existence was already validated by CurrencyCommandService::assertExists().
        // The Repository trusts its caller and focuses solely on persistence.
        // Note: rowCount() is intentionally NOT used here — MySQL skips the write
        // when is_active already holds the same value, giving rowCount() = 0 even
        // for an existing row.
        $stmt = $this->prepareOrFail(
            'UPDATE `currencies` SET `is_active` = :is_active WHERE `id` = :id',
        );
        $stmt->execute([
            ':is_active' => $command->isActive ? 1 : 0,
            ':id'        => $command->id,
        ]);

        return $this->fetchDtoOrFail($command->id);
    }

    // ================================================================== //
    //  STANDALONE RE-ORDER
    // ================================================================== //

    /**
     * {@inheritDoc}
     *
     * Algorithm (gap-free sequential ordering):
     *   Moving DOWN  (newOrder > oldOrder):
     *       rows in range (oldOrder, newOrder] shift up   (display_order − 1)
     *   Moving UP    (newOrder < oldOrder):
     *       rows in range [newOrder, oldOrder) shift down (display_order + 1)
     *
     * fetchDisplayOrder is called INSIDE the transaction so that the read
     * and all subsequent writes are atomic — same pattern used in update().
     */
    public function reorder(int $id, int $newOrder): void
    {
        $this->pdo->beginTransaction();
        try {
            $oldOrder = $this->fetchDisplayOrder($id);

            if ($oldOrder !== $newOrder) {
                $this->shiftRows($id, $oldOrder, $newOrder);

                $stmt = $this->prepareOrFail(
                    'UPDATE `currencies` SET `display_order` = ? WHERE `id` = ?',
                );
                $stmt->execute([$newOrder, $id]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            if ($e instanceof CurrencyExceptionInterface) {
                throw $e;
            }
            throw CurrencyPersistenceException::fromThrowable($e);
        }
    }

    // ================================================================== //
    //  TRANSLATION — UPSERT
    // ================================================================== //

    /** {@inheritDoc} */
    public function upsertTranslation(UpsertCurrencyTranslationCommand $command): CurrencyTranslationDTO
    {
        // Row alias syntax (MySQL 8.0.19+) — VALUES() is deprecated since 8.0.20.
        $stmt = $this->prepareOrFail(
            'INSERT INTO `currency_translations` (`currency_id`, `language_id`, `name`) 
                VALUES (:currency_id, :language_id, :insert_name) 
                ON DUPLICATE KEY UPDATE `name` = :update_name',
        );

        try {
            $stmt->execute([
                ':currency_id' => $command->currencyId,
                ':language_id' => $command->languageId,
                ':insert_name' => $command->translatedName,
                ':update_name' => $command->translatedName,
            ]);
        } catch (\PDOException $e) {
            // MySQL 1452: FK violation on language_id — language does not exist.
            // currency_id FK is already guarded by CurrencyCommandService::assertExists().
            if ($this->isForeignKeyViolation($e)) {
                throw CurrencyInvalidArgumentException::invalidLanguageId($command->languageId);
            }
            throw CurrencyPersistenceException::fromPdoException($e);
        }

        return $this->fetchTranslationOrFail($command->currencyId, $command->languageId);
    }

    // ================================================================== //
    //  TRANSLATION — DELETE
    // ================================================================== //

    /** {@inheritDoc} */
    public function deleteTranslation(DeleteCurrencyTranslationCommand $command): void
    {
        $stmt = $this->prepareOrFail(
            'DELETE FROM `currency_translations`
             WHERE `currency_id` = ? AND `language_id` = ?',
        );
        $stmt->execute([$command->currencyId, $command->languageId]);
        // Silent no-op if rowCount() === 0 — COALESCE handles the fallback.
    }

    // ================================================================== //
    //  Private — re-sort logic
    // ================================================================== //

    /**
     * Shifts the rows that sit between oldOrder and newOrder.
     * Assumes a transaction is already open.
     */
    private function shiftRows(int $excludeId, int $oldOrder, int $newOrder): void
    {
        if ($newOrder > $oldOrder) {
            /*
             * Moving DOWN ↓ — close the gap left by the moving row.
             * Rows in (oldOrder … newOrder] slide UP by 1.
             *
             *  Before:  1  [2]  3   4   5      target moves to 4
             *  Shift :  1   _   2   3   5
             *  Set   :  1   _   2   3  [4]  5  ← done in the caller
             */
            $sql    = 'UPDATE `currencies`
                       SET    `display_order` = `display_order` - 1
                       WHERE  `display_order` >  ?
                         AND  `display_order` <= ?
                         AND  `id`            != ?';
            $params = [$oldOrder, $newOrder, $excludeId];
        } else {
            /*
             * Moving UP ↑ — make room for the moving row.
             * Rows in [newOrder … oldOrder) slide DOWN by 1.
             *
             *  Before:  1   2   3  [4]  5      target moves to 2
             *  Shift :  1   3   4   _   5
             *  Set   :  1  [2]  3   4   5      ← done in the caller
             */
            $sql    = 'UPDATE `currencies`
                       SET    `display_order` = `display_order` + 1
                       WHERE  `display_order` >= ?
                         AND  `display_order` <  ?
                         AND  `id`            != ?';
            $params = [$newOrder, $oldOrder, $excludeId];
        }

        $this->prepareOrFail($sql)->execute($params);
    }

    // ================================================================== //
    //  Private — DB helpers
    // ================================================================== //

    private function fetchDisplayOrder(int $id): int
    {
        // FOR UPDATE acquires an exclusive row lock, preventing concurrent
        // transactions from reading the same display_order and causing a
        // double-shift. Must be called inside an open transaction.
        $stmt = $this->prepareOrFail(
            'SELECT `display_order` FROM `currencies` WHERE `id` = ? LIMIT 1 FOR UPDATE',
        );
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) {
            throw CurrencyNotFoundException::withId($id);
        }

        /** @var array<string, mixed> $row */
        $order = $row['display_order'];

        if (is_int($order)) {
            return $order;
        }

        if (is_numeric($order)) {
            return (int) $order;
        }

        throw CurrencyPersistenceException::unexpectedColumnType('display_order', $id);
    }

    private function fetchDtoOrFail(int $id): CurrencyDTO
    {
        $stmt = $this->prepareOrFail(
            'SELECT * FROM `currencies` WHERE `id` = ? LIMIT 1',
        );
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) {
            throw CurrencyNotFoundException::withId($id);
        }

        /** @var array<string, mixed> $row */
        return CurrencyDTO::fromRow($row);
    }

    private function fetchTranslationOrFail(int $currencyId, int $languageId): CurrencyTranslationDTO
    {
        // Delegate to the query reader — it owns the JOIN SELECT logic and the
        // TRANSLATION_SELECT constant, so we never duplicate that SQL here.
        $dto = $this->queryReader->findTranslation($currencyId, $languageId);

        if ($dto === null) {
            throw CurrencyTranslationNotFoundException::for($currencyId, $languageId);
        }

        return $dto;
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw CurrencyPersistenceException::prepareFailed($sql);
        }

        return $stmt;
    }

    /**
     * Returns true when the PDOException is MySQL error 1062 (Duplicate entry).
     * Used to translate DB-level unique constraint violations into domain exceptions.
     */
    private function isDuplicateKeyError(\PDOException $e): bool
    {
        return $e->getCode() === '23000'
               && str_contains($e->getMessage(), '1062');
    }

    /**
     * Returns true when the PDOException is MySQL error 1452 (FK constraint fails).
     * Used to translate a missing foreign key reference into a domain validation error.
     */
    private function isForeignKeyViolation(\PDOException $e): bool
    {
        return $e->getCode() === '23000'
               && str_contains($e->getMessage(), '1452');
    }
}
