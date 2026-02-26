<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

readonly class RedirectTokenResponseDTO implements \JsonSerializable
{
    public function __construct(
        public string $token,
        public string $redirect_url
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'token' => $this->token,
            'redirect_url' => $this->redirect_url,
        ];
    }
}
