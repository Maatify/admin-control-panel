<?php

declare(strict_types=1);

namespace App\Domain\DTO\Admin;

use JsonSerializable;

readonly class CreateAdminRequestDTO implements JsonSerializable
{
    public function __construct(
        public string $email,
        public string $password
    ) {
    }

    /**
     * @return array{email: string, password: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
