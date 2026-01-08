<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 12:16
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Crypto\HKDF;

use App\Modules\Crypto\HKDF\Exceptions\InvalidContextException;

final class HKDFContext
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidContextException('HKDF context must not be empty.');
        }

        if (! str_contains($value, ':v')) {
            throw new InvalidContextException('HKDF context must be versioned (e.g. "email:payload:v1").');
        }

        if (strlen($value) > 255) {
            throw new InvalidContextException('HKDF context is too long.');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
