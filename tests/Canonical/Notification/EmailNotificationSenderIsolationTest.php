<?php

declare(strict_types=1);

namespace Tests\Canonical\Notification;

use App\Infrastructure\Notification\EmailNotificationSender;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class EmailNotificationSenderIsolationTest extends TestCase
{
    public function test_constructor_only_depends_on_queue_writer(): void
    {
        $reflector = new ReflectionClass(EmailNotificationSender::class);
        $constructor = $reflector->getConstructor();

        $parameters = $constructor->getParameters();
        $this->assertCount(1, $parameters, 'Constructor should have exactly one dependency');

        $param = $parameters[0];
        $type = $param->getType();
        $this->assertNotNull($type, 'Dependency must be typed');
        $this->assertEquals(
            'App\Modules\Email\Queue\EmailQueueWriterInterface',
            $type->getName(),
            'Dependency must be EmailQueueWriterInterface'
        );
    }

    public function test_class_does_not_reference_transport_or_renderer_classes(): void
    {
        // This is a static analysis style test using reflection and file content
        // to ensure forbidden classes are not used.

        $forbiddenStrings = [
            'EmailTransportInterface',
            'SmtpEmailTransport',
            'PHPMailer',
            'EmailRendererInterface',
            'TwigEmailRenderer',
            'Swift_Mailer',
            'Symfony\Component\Mailer',
        ];

        $filePath = (new ReflectionClass(EmailNotificationSender::class))->getFileName();
        $content = file_get_contents($filePath);

        foreach ($forbiddenStrings as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $content,
                "EmailNotificationSender must not reference $forbidden"
            );
        }
    }
}
