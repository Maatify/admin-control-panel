<?php

declare(strict_types=1);

namespace App\Modules\Email\Transport;

use App\Modules\Email\DTO\RenderedEmailDTO;
use App\Modules\Email\Exception\EmailTransportException;

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
