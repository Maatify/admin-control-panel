<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 00:11
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Scope\DTO;

use JsonSerializable;

/**
 * @phpstan-type I18nScopeDropdownResponseArray array{
 *   data: I18nScopeDropdownItemDTO[]
 * }
 */
class I18nScopeDropdownResponseDTO  implements JsonSerializable
{
    /**
     * @param I18nScopeDropdownItemDTO[] $data
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * @return I18nScopeDropdownResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}
