<?php

declare(strict_types=1);

namespace App\Modules\Email\Config;

readonly class EmailTransportConfigDTO
{
    public function __construct(
        public string $host,
        public int $port,
        public string $username,
        public string $password,
        public string $fromAddress,
        public string $fromName,
        public ?string $encryption = null,
        public int $timeoutSeconds = 10,
        public string $charset = 'UTF-8',
        public int $debugLevel = 0
    ) {
    }
}
