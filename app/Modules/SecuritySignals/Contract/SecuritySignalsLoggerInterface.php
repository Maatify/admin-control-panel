<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Contract;

use Maatify\SecuritySignals\DTO\SecuritySignalRecordDTO;
use Maatify\SecuritySignals\Exception\SecuritySignalsStorageException;

interface SecuritySignalsLoggerInterface
{
    /**
     * Persist a security signal record.
     *
     * @throws SecuritySignalsStorageException
     */
    public function write(SecuritySignalRecordDTO $record): void;
}
