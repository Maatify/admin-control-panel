<?php

declare(strict_types=1);

namespace App\Domain\DTO\Request;

use LogicException;

class CreateAdminEmailRequestDTO
{
    public readonly string $email;

    public function __construct(string $email)
    {
        $this->email = trim(strtolower($email));

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new LogicException('Invalid email format');
        }
    }
}
