<?php

declare(strict_types=1);

namespace Tests\Phase5;

use App\Domain\DTO\Notification\DeliveryResultDTO;
use App\Domain\DTO\Notification\NotificationDeliveryDTO;
use App\Infrastructure\Notification\EmailNotificationSender;
use App\Modules\Email\Queue\DTO\EmailQueuePayloadDTO;
use App\Modules\Email\Queue\EmailQueueWriterInterface;
use PHPUnit\Framework\TestCase;

class EmailNotificationSenderTest extends TestCase
{
    public function test_send_enqueues_email_payload(): void
    {
        // Arrange
        $writer = $this->createMock(EmailQueueWriterInterface::class);
        $sender = new EmailNotificationSender($writer);

        $notificationId = '123';
        $recipient = 'test@example.com';
        $channel = 'email';
        $title = 'Test Title';
        $body = 'Test Body';
        $context = ['foo' => 'bar'];
        $createdAt = new \DateTimeImmutable();

        $deliveryDTO = new NotificationDeliveryDTO(
            $notificationId,
            $channel,
            $recipient,
            $title,
            $body,
            $context,
            $createdAt
        );

        // Expectation
        $writer->expects($this->once())
            ->method('enqueue')
            ->with(
                'notification',
                $notificationId,
                $recipient,
                $this->callback(function (EmailQueuePayloadDTO $payload) use ($context, $title, $body) {
                    $payloadContext = $payload->context;
                    // Check context merging logic
                    return $payload->templateKey === 'notification_generic'
                        && $payload->language === 'en'
                        && $payloadContext['foo'] === 'bar'
                        && $payloadContext['title'] === $title
                        && $payloadContext['body'] === $body;
                }),
                1 // System Sender Type
            );

        // Act
        $result = $sender->send($deliveryDTO);

        // Assert
        $this->assertInstanceOf(DeliveryResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals($notificationId, $result->notificationId);
        $this->assertEquals($channel, $result->channel);
    }

    public function test_send_handles_custom_template_and_language(): void
    {
        // Arrange
        $writer = $this->createMock(EmailQueueWriterInterface::class);
        $sender = new EmailNotificationSender($writer);

        $context = [
            'template_key' => 'custom_template',
            'language' => 'fr'
        ];

        $deliveryDTO = new NotificationDeliveryDTO(
            '1',
            'email',
            'test@example.com',
            'T',
            'B',
            $context,
            new \DateTimeImmutable()
        );

        // Expectation
        $writer->expects($this->once())
            ->method('enqueue')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->callback(function (EmailQueuePayloadDTO $payload) {
                    return $payload->templateKey === 'custom_template'
                        && $payload->language === 'fr';
                }),
                $this->anything()
            );

        // Act
        $sender->send($deliveryDTO);
    }

    public function test_supports_only_email_channel(): void
    {
        $writer = $this->createMock(EmailQueueWriterInterface::class);
        $sender = new EmailNotificationSender($writer);

        $this->assertTrue($sender->supports('email'));
        $this->assertFalse($sender->supports('sms'));
        $this->assertFalse($sender->supports('telegram'));
    }

    public function test_send_returns_failure_on_exception(): void
    {
         // Arrange
         $writer = $this->createMock(EmailQueueWriterInterface::class);
         $writer->method('enqueue')->willThrowException(new \RuntimeException('Queue error'));
         $sender = new EmailNotificationSender($writer);

         $deliveryDTO = new NotificationDeliveryDTO(
             '1', 'email', 'test@example.com', 'T', 'B', [], new \DateTimeImmutable()
         );

         // Act
         $result = $sender->send($deliveryDTO);

         // Assert
         $this->assertFalse($result->success);
         $this->assertStringContainsString('Queue error', $result->errorReason);
    }
}
