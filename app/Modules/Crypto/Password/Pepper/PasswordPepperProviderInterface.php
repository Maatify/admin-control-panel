<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 12:58
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Crypto\Password\Pepper;

use App\Modules\Crypto\Password\Exception\PepperUnavailableException;

interface PasswordPepperProviderInterface
{
    /**
     * @throws PepperUnavailableException when pepper is unavailable
     */
    public function getPepper(): string;
}
