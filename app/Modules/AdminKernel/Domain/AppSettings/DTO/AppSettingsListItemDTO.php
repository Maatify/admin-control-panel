<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\AppSettings\DTO;

use JsonSerializable;

/**
 * @phpstan-type AppSettingsListItemArray array{
 *     id: int,
 *     setting_group: string,
 *     setting_key: string,
 *     setting_value: string,
 *     is_active: int,
 *     is_protected: bool,
 *     is_editable: bool
 * }
 */
final readonly class AppSettingsListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $setting_group,
        public string $setting_key,
        public string $setting_value,
        public int $is_active,
        public bool $is_protected,
        public bool $is_editable,
    ) {
    }

    /**
     * @return AppSettingsListItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'setting_group' => $this->setting_group,
            'setting_key' => $this->setting_key,
            'setting_value' => $this->setting_value,
            'is_active' => $this->is_active,
            'is_protected' => $this->is_protected,
            'is_editable' => $this->is_editable,
        ];
    }
}

