<?php

declare(strict_types=1);

namespace App\Domain\DTO\Response;

use JsonSerializable;

class AdminEmailResponseDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $adminId,
        public readonly ?string $email    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'admin_id' => $this->adminId,
            'email' => $this->email,
        ];
    }
}
