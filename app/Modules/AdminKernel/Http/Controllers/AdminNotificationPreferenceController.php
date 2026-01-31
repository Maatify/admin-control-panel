<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\AdminNotificationPreferenceReaderInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminNotificationPreferenceWriterInterface;
use Maatify\AdminKernel\Domain\DTO\Notification\Preference\GetAdminPreferencesQueryDTO;
use Maatify\AdminKernel\Domain\DTO\Notification\Preference\UpdateAdminNotificationPreferenceDTO;
use Maatify\AdminKernel\Domain\Notification\NotificationChannelType;
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
        private ValidationGuard $validationGuard,
    ) {
    }

    public function getPreferences(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext) {
            throw new \RuntimeException('AdminContext missing');
        }
        $adminId = $adminContext->adminId;

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
        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext) {
            throw new \RuntimeException('AdminContext missing');
        }

        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$requestContext instanceof RequestContext) {
            throw new \RuntimeException('RequestContext missing');
        }

        $adminId = $adminContext->adminId;

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
