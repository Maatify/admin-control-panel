<?php

declare(strict_types=1);

namespace App\Domain\Session\Reader;

use App\Domain\DTO\Session\SessionListQueryDTO;
use App\Domain\DTO\Session\SessionListResponseDTO;

interface SessionListReaderInterface
{
    public function getSessions(SessionListQueryDTO $query): SessionListResponseDTO;
}
