<?php

declare(strict_types=1);

namespace Maatify\ImageProfile\DTO;

use JsonSerializable;

final class ImageValidationResultDTO implements JsonSerializable
{
    /** @param list<string> $errors */
    public function __construct(
        public readonly bool $isValid,
        public readonly string $profileCode,
        public readonly array $errors = [],
    ) {}

    public static function success(string $profileCode): self
    {
        return new self(true, $profileCode, []);
    }

    public static function failed(string $profileCode, string $error): self
    {
        return new self(false, $profileCode, [$error]);
    }

    /** @return array{is_valid: bool, profile_code: string, errors: list<string>} */
    public function jsonSerialize(): array
    {
        return [
            'is_valid' => $this->isValid,
            'profile_code' => $this->profileCode,
            'errors' => $this->errors,
        ];
    }
}
