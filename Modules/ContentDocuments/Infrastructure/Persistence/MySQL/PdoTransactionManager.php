<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Infrastructure\Persistence\MySQL;

use Maatify\ContentDocuments\Domain\Contract\Transaction\TransactionManagerInterface;
use PDO;

final readonly class PdoTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private PDO $pdo,
    )
    {
    }

    public function begin(): void
    {
        if (! $this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}
