<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

final readonly class I18nStatCountDTO implements \JsonSerializable
{
    public function __construct(
        public string $label,
        public int    $count,
    ) {}

    public function jsonSerialize(): mixed
    {
        return [
            'label' => $this->label,
            'count' => $this->count,
        ];
    }
}
