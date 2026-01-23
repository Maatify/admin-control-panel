<?php

declare(strict_types=1);

namespace Maatify\SecuritySignals\Contract;

use Maatify\SecuritySignals\DTO\SecuritySignalDTO;
use Maatify\SecuritySignals\Exception\SecuritySignalWriteException;

interface SecuritySignalLoggerInterface
{
    /**
     * @throws SecuritySignalWriteException
     */
    public function log(SecuritySignalDTO $dto): void;
}
