<?php

declare(strict_types=1);

namespace Tests\Support;

use PDO;

class NestedTransactionPDO extends PDO
{
    private int $transactionDepth = 0;

    public function beginTransaction(): bool
    {
        if ($this->transactionDepth === 0) {
            $this->transactionDepth++;
            return parent::beginTransaction();
        }
        $this->transactionDepth++;
        return true;
    }

    public function commit(): bool
    {
        $this->transactionDepth--;
        if ($this->transactionDepth === 0) {
            return parent::commit();
        }
        return true;
    }

    public function rollBack(): bool
    {
        if ($this->transactionDepth > 0) {
            $this->transactionDepth = 0;
            return parent::rollBack();
        }
        return false;
    }
}
