<?php

declare(strict_types=1);

namespace App\Domain\DTO\Response;

use App\Domain\Enum\IdentifierType;
use JsonSerializable;

class AdminEmailResponseDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $adminId,
        public readonly ?string $email    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'admin_id' => $this->adminId,
            IdentifierType::EMAIL->value => $this->email,
        ];
    }
}
