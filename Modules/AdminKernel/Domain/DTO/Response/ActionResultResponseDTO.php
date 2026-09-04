<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Response;

use JsonSerializable;

readonly class ActionResultResponseDTO implements JsonSerializable
{
    public function __construct(
        public ?int $adminId = null,
        public ?string $createdAt = null,
        public ?bool $emailAdded = null,
        public ?bool $exists = null
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
