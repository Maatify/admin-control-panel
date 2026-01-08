<?php

declare(strict_types=1);

namespace App\Modules\Crypto\DX;

use App\Modules\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use App\Modules\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use App\Modules\Crypto\Reversible\ReversibleCryptoService;
use App\Modules\Crypto\KeyRotation\KeyRotationService;

/**
 * CryptoDirectFactory
 *
 * Automates the KeyRotation -> ReversibleCrypto pipeline.
 *
 * This factory creates a ReversibleCryptoService configured with
 * RAW root keys directly from the KeyRotationService.
 *
 * PIPELINE:
 * KeyRotation (Root Keys) -> ReversibleCrypto (Encryption)
 *
 * @internal This is a DX helper.
 */
final readonly class CryptoDirectFactory
{
    public function __construct(
        private KeyRotationService $keyRotation,
        private ReversibleCryptoAlgorithmRegistry $registry
    ) {
    }

    /**
     * Create a ReversibleCryptoService using direct root keys.
     *
     * @param ReversibleCryptoAlgorithmEnum $algorithm Default: AES_256_GCM
     * @return ReversibleCryptoService
     */
    public function create(
        ReversibleCryptoAlgorithmEnum $algorithm = ReversibleCryptoAlgorithmEnum::AES_256_GCM
    ): ReversibleCryptoService {
        $export = $this->keyRotation->exportForCrypto();

        /** @var array<string,string> $keys */
        $keys = $export['keys'];
        /** @var string $activeKeyId */
        $activeKeyId = $export['active_key_id'];

        return new ReversibleCryptoService(
            $this->registry,
            $keys,
            $activeKeyId,
            $algorithm
        );
    }
}
