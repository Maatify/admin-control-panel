<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\AdminNotificationPreferenceReaderInterface;
use App\Domain\Contracts\AdminNotificationPreferenceWriterInterface;
use App\Domain\DTO\Notification\Preference\GetAdminPreferencesQueryDTO;
use App\Domain\DTO\Notification\Preference\UpdateAdminNotificationPreferenceDTO;
use App\Domain\Notification\NotificationChannelType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AdminNotificationPreferenceController
{
    public function __construct(
        private AdminNotificationPreferenceReaderInterface $reader,
        private AdminNotificationPreferenceWriterInterface $writer
    ) {
    }

    public function getPreferences(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $adminId = (int)$request->getAttribute('admin_id');
        $query = new GetAdminPreferencesQueryDTO($adminId);
        $preferences = $this->reader->getPreferences($query);

        $payload = json_encode($preferences);
        if ($payload === false) {
             throw new \RuntimeException('JSON encoding failed');
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function upsertPreference(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $adminId = (int)$request->getAttribute('admin_id');
        /** @var array<string, mixed> $body */
        $body = $request->getParsedBody();

        $notificationType = $body['notification_type'] ?? null;
        $channelTypeStr = $body['channel_type'] ?? null;
        $isEnabled = $body['is_enabled'] ?? null;

        if (!is_string($notificationType) || !is_string($channelTypeStr) || !is_bool($isEnabled)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid input']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $channelType = NotificationChannelType::tryFrom($channelTypeStr);
        if ($channelType === null) {
            $response->getBody()->write(json_encode(['error' => 'Invalid channel type']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $dto = new UpdateAdminNotificationPreferenceDTO(
            $adminId,
            $notificationType,
            $channelType,
            $isEnabled
        );

        $result = $this->writer->upsertPreference($dto);

        $payload = json_encode($result);
        if ($payload === false) {
             throw new \RuntimeException('JSON encoding failed');
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
