<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\AdminNotificationPreferenceReaderInterface;
use App\Domain\Contracts\AdminNotificationPreferenceWriterInterface;
use App\Domain\DTO\Notification\Preference\GetAdminPreferencesQueryDTO;
use App\Domain\DTO\Notification\Preference\UpdateAdminNotificationPreferenceDTO;
use App\Domain\Notification\NotificationChannelType;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\AdminPreferenceGetSchema;
use App\Modules\Validation\Schemas\AdminPreferenceUpsertSchema;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AdminNotificationPreferenceController
{
    public function __construct(
        private AdminNotificationPreferenceReaderInterface $reader,
        private AdminNotificationPreferenceWriterInterface $writer,
        private ValidationGuard $validationGuard
    ) {
    }

    public function getPreferences(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $adminId = $request->getAttribute('admin_id');
        if (!is_int($adminId)) {
            throw new \RuntimeException('Invalid admin_id');
        }

        $this->validationGuard->check(new AdminPreferenceGetSchema(), ['admin_id' => $adminId]);

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
        $adminId = $request->getAttribute('admin_id');
        if (!is_int($adminId)) {
            throw new \RuntimeException('Invalid admin_id');
        }
        /** @var array<string, mixed> $body */
        $body = (array)$request->getParsedBody();
        $body['admin_id'] = $adminId;

        $this->validationGuard->check(new AdminPreferenceUpsertSchema(), $body);

        /** @var string $notificationType */
        $notificationType = $body['notification_type'];
        /** @var string $channelTypeStr */
        $channelTypeStr = $body['channel_type'];
        /** @var bool $isEnabled */
        $isEnabled = $body['is_enabled'];

        $channelType = NotificationChannelType::tryFrom($channelTypeStr);
        if ($channelType === null) {
            $errorPayload = json_encode(['error' => 'Invalid channel type']);
            if ($errorPayload === false) {
                throw new \RuntimeException('JSON encoding failed');
            }
            $response->getBody()->write($errorPayload);
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
