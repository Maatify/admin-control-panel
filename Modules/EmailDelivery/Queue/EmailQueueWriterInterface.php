<?php

declare(strict_types=1);

namespace Maatify\EmailDelivery\Queue;

use DateTimeInterface;
use Maatify\EmailDelivery\Queue\DTO\EmailQueuePayloadDTO;

interface EmailQueueWriterInterface
{
    /**
     * Enqueues an email for sending.
     *
     * @param string $entityType The type of the entity associated with the email.
     * @param string|null $entityId The ID of the entity associated with the email.
     * @param string $recipientEmail The recipient's email address.
     * @param EmailQueuePayloadDTO $payload The email payload content.
     * @param int $senderType The type of sender.
     * @param int $priority Priority level (default: 5).
     * @param DateTimeInterface|null $scheduledAt Scheduled time for sending (null for immediate).
     *
     * @return void
     */
    public function enqueue(
        string $entityType,
        ?string $entityId,
        string $recipientEmail,
        EmailQueuePayloadDTO $payload,
        int $senderType,
        int $priority = 5,
        ?DateTimeInterface $scheduledAt = null
    ): void;
}
