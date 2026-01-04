<?php

declare(strict_types=1);

namespace App\Domain\DTO\Response;

use JsonSerializable;

class ActionResultResponseDTO implements JsonSerializable
{
    public function __construct(
        public readonly ?int $adminId = null,
        public readonly ?string $createdAt = null,
        public readonly ?bool $emailAdded = null,
        public readonly ?bool $exists = null
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [];

        if ($this->exists !== null) {
            $data['exists'] = $this->exists;
        }
        if ($this->adminId !== null) {
            $data['admin_id'] = $this->adminId;
        }
        if ($this->createdAt !== null) {
            $data['created_at'] = $this->createdAt;
        }
        if ($this->emailAdded !== null) {
            $data['email_added'] = $this->emailAdded;
        }

        return $data;
    }
}
