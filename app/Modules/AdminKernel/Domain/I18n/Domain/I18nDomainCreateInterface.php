<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 11:38
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Domain;

use Maatify\AdminKernel\Domain\DTO\I18n\Domains\I18nDomainCreateDTO;

interface I18nDomainCreateInterface
{
    public function create(I18nDomainCreateDTO $dto): int;

    /**
     * Admin-only existence check.
     *
     * This method is intentionally placed here as a privileged
     * control-plane validation for admin create/update flows.
     */
    public function existsByCode(string $code): bool;
}
