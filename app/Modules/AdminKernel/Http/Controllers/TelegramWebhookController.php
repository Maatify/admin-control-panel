<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers;

use Maatify\AdminKernel\Infrastructure\Notification\TelegramHandler;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\TelegramWebhookSchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

readonly class TelegramWebhookController
{
    public function __construct(
        private TelegramHandler $handler,
        private LoggerInterface $logger,
        private ValidationGuard $validationGuard
    ) {
    }

    public function handle(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();

        // Webhook specific: validation failure shouldn't 400 crash but we must guard it.
        // Actually ValidationGuard throws Exception which results in error response.
        // For webhooks, we usually want to return 200 OK even on error to stop retries from provider (Telegram).
        // BUT the task rule says "Controllers MUST NOT catch ValidationFailedException".
        // So I must let it bubble.
        $this->validationGuard->check(new TelegramWebhookSchema(), $data);

        // Extract Message
        $message = $data['message'] ?? null;
        if (!is_array($message)) {
            // Might be an edited_message or something else we ignore
            return $response->withStatus(200);
        }

        $chat = $message['chat'] ?? null;
        $text = $message['text'] ?? null;

        if (!is_array($chat) || !isset($chat['id']) || !is_string($text)) {
            return $response->withStatus(200);
        }

        $chatId = (string)$chat['id'];

        // Expected format: /start <OTP>
        $parts = explode(' ', trim($text), 2);

        if (count($parts) !== 2 || strtolower($parts[0]) !== '/start') {
            // Not a start command or missing OTP
            return $response->withStatus(200);
        }

        $otp = trim($parts[1]);

        // Process
        $resultMessage = $this->handler->handleStart($otp, $chatId);

        // Log result (Handler logs specific failures, we log high level)
        $this->logger->info('telegram_webhook_processed', [
            'chat_id' => $chatId,
            'result' => $resultMessage
        ]);

        return $response->withStatus(200);
    }
}
