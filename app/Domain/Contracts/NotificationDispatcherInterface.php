<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\NotificationMessageDTO;

interface NotificationDispatcherInterface
{
    public function dispatch(NotificationMessageDTO $message): void;
}
