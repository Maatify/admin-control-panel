<?php

declare(strict_types=1);

namespace App\Modules\Crypto\DX;

use App\Modules\Crypto\HKDF\HKDFContext;
use App\Modules\Crypto\HKDF\HKDFService;
use App\Modules\Crypto\KeyRotation\KeyRotationService;
use App\Modules\Crypto\Reversible\Registry\ReversibleCryptoAlgorithmRegistry;
use App\Modules\Crypto\Reversible\ReversibleCryptoAlgorithmEnum;
use App\Modules\Crypto\Reversible\ReversibleCryptoService;

/**
 * CryptoContextFactory
 *
 * Automates the KeyRotation -> HKDF -> ReversibleCrypto pipeline.
 *
 * This factory creates a ReversibleCryptoService configured with
 * DERIVED keys specific to the provided context string.
 *
 * PIPELINE:
 * KeyRotation (Root Keys) -> HKDF (Derivation) -> ReversibleCrypto (Encryption)
 *
 * @internal This is a DX helper.
 */
final readonly class CryptoContextFactory
{
    // AES-256 requires 32 bytes (256 bits) key length
    private const KEY_LENGTH = 32;

    public function __construct(
        private KeyRotationService $keyRotation,
        private HKDFService $hkdf,
        private ReversibleCryptoAlgorithmRegistry $registry
    ) {
    }

    /**
     * Create a ReversibleCryptoService using context-derived keys.
     *
     * @param string $contextString Explicit context string (must be versioned, e.g. "type:v1")
     * @param ReversibleCryptoAlgorithmEnum $algorithm Default: AES_256_GCM
     * @return ReversibleCryptoService
     */
    public function create(
        string $contextString,
        ReversibleCryptoAlgorithmEnum $algorithm = ReversibleCryptoAlgorithmEnum::AES_256_GCM
    ): ReversibleCryptoService {
        $export = $this->keyRotation->exportForCrypto();
        $context = new HKDFContext($contextString);

        /** @var array<string,string> $rootKeys */
        $rootKeys = $export['keys'];
        /** @var string $activeKeyId */
        $activeKeyId = $export['active_key_id'];

        $derivedKeys = [];
        foreach ($rootKeys as $keyId => $rootKey) {
            $derivedKeys[$keyId] = $this->hkdf->deriveKey(
                $rootKey,
                $context,
                self::KEY_LENGTH
            );
        }

        return new ReversibleCryptoService(
            $this->registry,
            $derivedKeys,
            $activeKeyId,
            $algorithm
        );
    }
}
