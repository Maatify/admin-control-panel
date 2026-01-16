<?php

declare(strict_types=1);

namespace Tests\Http\Controllers;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Service\StepUpService;
use App\Http\Controllers\StepUpController;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Validation\Guard\ValidationGuard;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\TelemetryTestHelper;

final class StepUpControllerTest extends TestCase
{
    public function testVerifySuccessRecordsTelemetry(): void
    {
        $stepUpService = $this->createMock(StepUpService::class);

        $validator = $this->createMock(\App\Modules\Validation\Contracts\ValidatorInterface::class);
        $validator->method('validate')->willReturn(TelemetryTestHelper::makeValidValidationResultDTO());
        $validationGuard = new ValidationGuard($validator);

        $helper = TelemetryTestHelper::makeFactoryWithSpyRecorder();
        $telemetryFactory = $helper['factory'];
        $spy = $helper['recorder'];

        $controller = new StepUpController(
            $stepUpService,
            $validationGuard,
            $telemetryFactory
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $request->method('getParsedBody')->willReturn(['code' => '123456']);
        $request->method('getCookieParams')->willReturn(['auth_token' => 'sess-123']);

        $adminContext = new AdminContext(123);
        $requestContext = new RequestContext('req-1', '1.2.3.4', 'test');

        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $requestContext],
        ]);

        $resultDto = TelemetryTestHelper::makeTotpVerificationResultDTO(true);
        $stepUpService->method('verifyTotp')->willReturn($resultDto);

        $response->method('getBody')->willReturn($stream);
        $response->method('withHeader')->willReturn($response);
        $response->method('withStatus')->willReturn($response);

        // Act
        $controller->verify($request, $response);

        // Assert
        $this->assertCount(1, $spy->records);
        $this->assertEquals(TelemetryEventTypeEnum::AUTH_STEPUP_SUCCESS, $spy->records[0]->eventType);
        $this->assertEquals(123, $spy->records[0]->actorId);
    }
}
