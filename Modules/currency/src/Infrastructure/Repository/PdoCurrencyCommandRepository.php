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
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;
use PDOStatement;
use Throwable;

final readonly class PdoCurrencyCommandRepository implements CurrencyCommandRepositoryInterface
{
    /**
     * @param CurrencyQueryReaderInterface $queryReader  Injected for translation read-back
     *                                                   after upsert — avoids duplicating
     *                                                   the JOIN SELECT from the query reader.
     */
    public function __construct(
        private PDO                          $pdo,
        private CurrencyQueryReaderInterface $queryReader,
        private ScopedOrderingManager $orderingManager = new ScopedOrderingManager(),
    ) {}

    /** {@inheritDoc} */
    public function create(CreateCurrencyCommand $command): CurrencyDTO
    {
        /*
         * display_order is intentionally owned by the repository ordering
         * environment. CreateCurrencyCommand::displayOrder is not trusted here;
         * callers must use reorder() for explicit ordering changes.
         */
        $displayOrder = $this->orderingManager->getNextPosition(
            $this->pdo,
            $this->orderingConfig(),
            null
        );

        $stmt = $this->prepareOrFail(
            'INSERT INTO `currencies`
                 (`code`, `name`, `symbol`, `is_active`, `display_order`)
             VALUES
                 (:code, :name, :symbol, :is_active, :display_order)',
        );

        // strtoupper is intentionally repeated here — the repository must not
        // assume callers always go through CurrencyCommandService.
        $params = [
            ':code'          => strtoupper($command->code),
            ':name'          => $command->name,
            ':symbol'        => $command->symbol,
            ':is_active'     => $command->isActive ? 1 : 0,
            ':display_order' => $displayOrder,
        ];

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
    //  UPDATE (full replace)
    // ================================================================== //

    /**
     * {@inheritDoc}
     *
     * display_order is intentionally not updated here. Ordering changes must go
     * through reorder(), which delegates to ScopedOrderingManager.
     */
    public function update(UpdateCurrencyCommand $command): CurrencyDTO
    {
        try {
            $stmt = $this->prepareOrFail(
                'UPDATE `currencies`
                 SET `code`      = :code,
                     `name`      = :name,
                     `symbol`    = :symbol,
                     `is_active` = :is_active
                 WHERE `id` = :id',
            );
            $stmt->execute([
                ':code'      => strtoupper($command->code),
                ':name'      => $command->name,
                ':symbol'    => $command->symbol,
                ':is_active' => $command->isActive ? 1 : 0,
                ':id'        => $command->id,
            ]);
        } catch (\PDOException $e) {
            if ($this->isDuplicateKeyError($e)) {
                throw CurrencyCodeAlreadyExistsException::withCode($command->code);
            }
            throw CurrencyPersistenceException::fromPdoException($e);
        } catch (Throwable $e) {
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
     * Delegates all ordering movement to ScopedOrderingManager.
     * The manager owns its transaction and must be called outside active PDO
     * transactions.
     */
    public function reorder(int $id, int $newOrder): void
    {
        try {
            $moved = $this->orderingManager->moveWithinScope(
                $this->pdo,
                $this->orderingConfig(),
                null,
                $id,
                $newOrder
            );

            if (!$moved) {
                throw CurrencyNotFoundException::withId($id);
            }
        } catch (Throwable $e) {
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
    //  Private — ordering config
    // ================================================================== //

    private function orderingConfig(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(
            table: 'currencies',
            scopeColumn: null,
            idColumn: 'id',
            orderColumn: 'display_order',
            deletedAtColumn: null,
        );
    }

    // ================================================================== //
    //  Private — DB helpers
    // ================================================================== //

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
