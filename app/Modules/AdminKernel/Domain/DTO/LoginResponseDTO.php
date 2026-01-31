<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

use JsonSerializable;

readonly class LoginResponseDTO implements JsonSerializable
{
    public function __construct(
        public string $token,
        public string $status = 'success'
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
