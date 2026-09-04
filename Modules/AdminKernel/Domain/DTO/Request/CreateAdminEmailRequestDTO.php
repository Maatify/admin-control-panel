<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Request;

use Maatify\AdminKernel\Domain\Exception\InvalidIdentifierFormatException;

readonly class CreateAdminEmailRequestDTO
{
    public string $email;

    public function __construct(string $email)
    {
        $this->email = trim(strtolower($email));

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidIdentifierFormatException();
        }
    }
}
