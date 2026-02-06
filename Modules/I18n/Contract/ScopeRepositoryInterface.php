<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-06 20:17
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Contract;

use Maatify\I18n\DTO\ScopeDTO;
use Maatify\I18n\DTO\ScopeCollectionDTO;

interface ScopeRepositoryInterface
{
    public function getByCode(string $code): ?ScopeDTO;

    public function listActive(): ScopeCollectionDTO;

    public function listAll(): ScopeCollectionDTO;
}
