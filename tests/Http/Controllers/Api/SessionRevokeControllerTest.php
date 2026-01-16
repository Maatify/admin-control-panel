<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Api;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Service\AuthorizationService;
use App\Domain\Service\SessionRevocationService;
use App\Http\Controllers\Api\SessionRevokeController;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Validation\Guard\ValidationGuard;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\TelemetryTestHelper;

final class SessionRevokeControllerTest extends TestCase
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

        $controller = new SessionRevokeController(
            $revocationService,
            $authzService,
            $validationGuard,
            $telemetryFactory
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

        $response->method('getBody')->willReturn($stream);
        $response->method('withHeader')->willReturn($response);
        $response->method('withStatus')->willReturn($response);

        // Act
        $controller($request, $response, ['session_id' => 'hash-456']);

        // Assert
        $this->assertCount(1, $spy->records);
        $this->assertEquals(TelemetryEventTypeEnum::RESOURCE_MUTATION, $spy->records[0]->eventType);
        $this->assertEquals('session_revoke', $spy->records[0]->metadata['action']);
    }
}
