<?php

declare(strict_types=1);

namespace Maatify\ContentDocuments\Domain\Contract\Transaction;

interface TransactionManagerInterface
{
    public function begin(): void;

    public function commit(): void;

    public function rollback(): void;

    public function inTransaction(): bool;
}

