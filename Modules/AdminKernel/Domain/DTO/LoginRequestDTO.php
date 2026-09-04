<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

use JsonSerializable;

readonly class LoginRequestDTO implements JsonSerializable
{
    public function __construct(
        public string $email,
        public string $password
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
