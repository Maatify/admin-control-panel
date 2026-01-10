<?php

declare(strict_types=1);

namespace App\Domain\DTO;

readonly class AdminConfigDTO
{
    public function __construct(
        public string $appEnv,
        public bool $appDebug,
        public string $timezone,
        public string $passwordPepper,
        public ?string $passwordPepperOld,
        public string $emailBlindIndexKey,
        public string $emailEncryptionKey,
        public string $dbHost,
        public string $dbName,
        public string $dbUser,
        public string $dbPass,
        public bool $isRecoveryMode,
        /** @var array<int, array{id: string, key: string}> */
        public array $cryptoKeys = [],
        public ?string $activeKeyId = null,
    ) {
    }
}
