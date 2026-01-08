<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 13:17
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Tests\Modules\Crypto\Password;

use App\Modules\Crypto\Password\Pepper\PasswordPepperProviderInterface;
use App\Modules\Crypto\Password\Exception\PepperUnavailableException;

final class FakePepperProvider implements PasswordPepperProviderInterface
{
    private string $pepper;

    public function __construct(string $pepper)
    {
        $this->pepper = $pepper;
    }

    public function getPepper(): string
    {
        if ($this->pepper === '') {
            throw new PepperUnavailableException('Pepper is empty');
        }

        return $this->pepper;
    }
}
