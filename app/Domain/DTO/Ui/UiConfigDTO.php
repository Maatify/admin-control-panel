<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-28 14:05
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\DTO\Ui;

class UiConfigDTO
{
    public function __construct(

        public string $adminAssetBaseUrl = '/',
        public string $appName = 'Admin Panel',
        public ?string $logoUrl = null,
        public string $adminUrl = '/'
    )
    {
    }
}