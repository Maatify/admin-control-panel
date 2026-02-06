<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/i18n
 * @Project     maatify:i18n
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-06 20:18
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/i18n view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\I18n\Contract;

use Maatify\I18n\DTO\DomainDTO;
use Maatify\I18n\DTO\DomainCollectionDTO;

interface DomainRepositoryInterface
{
    public function getByCode(string $code): ?DomainDTO;

    public function listActive(): DomainCollectionDTO;

    public function listAll(): DomainCollectionDTO;

    /**
     * @param list<string> $codes
     */
    public function listByCodes(array $codes): DomainCollectionDTO;
}
