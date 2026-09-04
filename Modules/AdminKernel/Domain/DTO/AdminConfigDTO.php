<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

readonly class AdminConfigDTO
{
    public function __construct(
        public string $appEnv,
        public bool $appDebug,
        public string $timezone,
        public string $passwordActivePepperId,
        public string $dbHost,
        public string $dbName,
        public string $dbUser,
        public bool $isRecoveryMode,
        public ?string $activeKeyId = null,
        public bool $hasCryptoKeyRing = false,
        public bool $hasPasswordPepperRing = false,
    ) {
    }
}
