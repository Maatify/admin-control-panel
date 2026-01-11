<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Domain\Contracts\NotificationSenderInterface;
use App\Domain\DTO\Notification\DeliveryResultDTO;
use App\Domain\DTO\Notification\NotificationDeliveryDTO;
use App\Modules\Email\Queue\DTO\EmailQueuePayloadDTO;
use App\Modules\Email\Queue\EmailQueueWriterInterface;
use DateTimeImmutable;
use Throwable;

class EmailNotificationSender implements NotificationSenderInterface
{
    // Assumption: senderType 1 represents "System/Notification"
    private const SENDER_TYPE_SYSTEM = 1;

    public function __construct(
        private readonly EmailQueueWriterInterface $queueWriter
    ) {
    }

    public function supports(string $channel): bool
    {
        return $channel === 'email';
    }

    public function send(NotificationDeliveryDTO $delivery): DeliveryResultDTO
    {
        // Simple validation
        if (trim($delivery->recipient) === '') {
            return new DeliveryResultDTO(
                $delivery->notificationId,
                $delivery->channel,
                false,
                'Empty recipient',
                new DateTimeImmutable()
            );
        }

        try {
            // Extract template and language from context with defaults
            $templateKey = isset($delivery->context['template_key']) && is_string($delivery->context['template_key'])
                ? $delivery->context['template_key']
                : 'notification_generic';

            $language = isset($delivery->context['language']) && is_string($delivery->context['language'])
                ? $delivery->context['language']
                : 'en';

            $payload = new EmailQueuePayloadDTO(
                $delivery->title,
                $delivery->body,
                $templateKey,
                $language
            );

            $this->queueWriter->enqueue(
                'notification',
                $delivery->notificationId,
                $delivery->recipient,
                $payload,
                self::SENDER_TYPE_SYSTEM
            );

            return new DeliveryResultDTO(
                $delivery->notificationId,
                $delivery->channel,
                true,
                null,
                new DateTimeImmutable()
            );
        } catch (Throwable $e) {
            return new DeliveryResultDTO(
                $delivery->notificationId,
                $delivery->channel,
                false,
                'Enqueue failed: ' . $e->getMessage(),
                new DateTimeImmutable()
            );
        }
    }
}
