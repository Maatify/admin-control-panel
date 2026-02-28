<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 01:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Domain;

use Maatify\AdminKernel\Domain\I18n\Domain\DTO\I18nDomainDetailsDTO;

interface I18nDomainDetailsReaderInterface
{
    public function getDomainDetailsById(int $id): I18nDomainDetailsDTO;
}
