<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use JsonSerializable;

class LoginRequestDTO implements JsonSerializable
{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password
        ];
    }
}
