<?php

declare(strict_types=1);

namespace App\Domain\DTO\Request;

use App\Domain\Exception\InvalidIdentifierFormatException;
use InvalidArgumentException;

readonly class CreateAdminRequestDTO
{
    public string $email;
    public string $password;
    public string $passwordConfirmation;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $passwordConfirmation = $data['password_confirmation'] ?? null;

        if (!is_string($email) || empty($email)) {
            throw new InvalidIdentifierFormatException('Invalid or missing email');
        }

        if (!is_string($password) || empty($password)) {
            throw new InvalidArgumentException('Password is required');
        }

        if (!is_string($passwordConfirmation) || empty($passwordConfirmation)) {
            throw new InvalidArgumentException('Password confirmation is required');
        }

        if ($password !== $passwordConfirmation) {
            throw new InvalidArgumentException('Passwords do not match');
        }

        $this->email = trim(strtolower($email));

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidIdentifierFormatException('Invalid email format');
        }

        $this->password = $password;
        $this->passwordConfirmation = $passwordConfirmation;
    }
}
