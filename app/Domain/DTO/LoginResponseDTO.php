<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use JsonSerializable;

class LoginResponseDTO implements JsonSerializable
{
    public function __construct(
        public readonly string $token,
        public readonly string $status = 'success'
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status,
            'token' => $this->token
        ];
    }
}
