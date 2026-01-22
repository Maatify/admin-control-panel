<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Api;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Service\AuthorizationService;
use App\Domain\Service\SessionRevocationService;
use App\Http\Controllers\Api\SessionBulkRevokeController;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Validation\Guard\ValidationGuard;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\TelemetryTestHelper;

final class SessionBulkRevokeControllerTest extends TestCase
{
    public function testInvokeSuccessRecordsTelemetry(): void
    {
        $revocationService = $this->createMock(SessionRevocationService::class);
        $authzService = $this->createMock(AuthorizationService::class);

        $validator = $this->createMock(\App\Modules\Validation\Contracts\ValidatorInterface::class);
        $validator->method('validate')->willReturn(TelemetryTestHelper::makeValidValidationResultDTO());
        $validationGuard = new ValidationGuard($validator);

        $helper = TelemetryTestHelper::makeFactoryWithSpyRecorder();
        $telemetryFactory = $helper['factory'];
        $spy = $helper['recorder'];

        $writer = $this->createMock(\App\Modules\ActivityLog\Contracts\ActivityLogWriterInterface::class);
        $writer->expects($this->once())->method('write');
        $activityRecorder = new \App\Domain\ActivityLog\Recorder\ActivityRecorder($writer);
        $adminActivityLogService = new \App\Domain\ActivityLog\Service\AdminActivityLogService($activityRecorder);

        $controller = new SessionBulkRevokeController(
            $revocationService,
            $authzService,
            $validationGuard,
            $telemetryFactory,
            $adminActivityLogService
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $adminContext = new AdminContext(123);
        $requestContext = new RequestContext('req-1', '1.2.3.4', 'test');

        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $requestContext],
        ]);

        $request->method('getCookieParams')->willReturn(['auth_token' => 'token-123']);
        $request->method('getParsedBody')->willReturn(['session_ids' => ['hash-1', 'hash-2']]);

        $response->method('getBody')->willReturn($stream);
        $response->method('withHeader')->willReturn($response);
        $response->method('withStatus')->willReturn($response);

        // Act
        $controller($request, $response);

        // Assert
        $this->assertCount(1, $spy->records);
        $this->assertEquals(TelemetryEventTypeEnum::RESOURCE_MUTATION, $spy->records[0]->eventType);
        $this->assertEquals('session_revoke_bulk', $spy->records[0]->metadata['action']);
    }
}
