<?php

declare(strict_types=1);

namespace Maatify\EmailDelivery\Transport;

use Maatify\EmailDelivery\DTO\RenderedEmailDTO;
use Maatify\EmailDelivery\Exception\EmailTransportException;

interface EmailTransportInterface
{
    /**
     * @throws EmailTransportException
     */
    public function send(
        string $recipientEmail,
        RenderedEmailDTO $email
    ): void;
}
