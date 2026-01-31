<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 12:18
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\HKDF;

final class HKDFService
{
    private HKDFKeyDeriver $deriver;

    public function __construct(?HKDFKeyDeriver $deriver = null)
    {
        $this->deriver = $deriver ?? new HKDFKeyDeriver();
    }

    public function deriveKey(
        string $rootKey,
        HKDFContext $context,
        int $length
    ): string
    {
        HKDFPolicy::assertValidRootKey($rootKey);
        HKDFPolicy::assertValidOutputLength($length);

        return $this->deriver->derive(
            $rootKey,
            $context->value(),
            $length
        );
    }
}
