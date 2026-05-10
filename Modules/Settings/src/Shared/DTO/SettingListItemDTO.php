<?php

declare(strict_types=1);

namespace Maatify\Settings\Shared\DTO;

final readonly class SettingListItemDTO implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $settingKey,
        public string $settingValue,
        public string $valueType,
        public bool $isAdminEditable,
        public ?string $adminNote,
        public string $updatedAt,
    ) {}

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'setting_key' => $this->settingKey,
            'setting_value' => $this->settingValue,
            'value_type' => $this->valueType,
            'is_admin_editable' => $this->isAdminEditable,
            'admin_note' => $this->adminNote,
            'updated_at' => $this->updatedAt,
        ];
    }
}
