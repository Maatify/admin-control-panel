<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 00:08
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Scope\DTO;

use JsonSerializable;

/**
 * @phpstan-type I18nScopeDropDownItemArray array{
 *   id:   int,
 *   code: string,
 *   name: string
 * }
 */
class I18nScopeDropdownItemDTO  implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name
    )
    {
    }

    /**
     * @return I18nScopeDropDownItemArray*/
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
        ];
    }
}
