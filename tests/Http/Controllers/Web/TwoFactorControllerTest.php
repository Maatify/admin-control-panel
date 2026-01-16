<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Web;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\Service\StepUpService;
use App\Http\Controllers\Web\TwoFactorController;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Tests\Support\TelemetryTestHelper;

final class TwoFactorControllerTest extends TestCase
{
    public function testDoSetupRecordsTelemetry(): void
    {
        $stepUpService = $this->createMock(StepUpService::class);
        $totpService = $this->createMock(TotpServiceInterface::class);
        $view = $this->createMock(Twig::class);

        $helper = TelemetryTestHelper::makeFactoryWithSpyRecorder();
        $telemetryFactory = $helper['factory'];
        $spy = $helper['recorder'];

        $controller = new TwoFactorController(
            $stepUpService,
            $totpService,
            $view,
            $telemetryFactory
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request->method('getParsedBody')->willReturn([
            'secret' => 'SECRET',
            'code' => '123456',
        ]);

        $request->method('getCookieParams')->willReturn(['auth_token' => 'sess-123']);

        $adminContext = new AdminContext(123);
        $requestContext = new RequestContext('req-1', '1.2.3.4', 'test');

        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $requestContext],
        ]);

        $stepUpService->method('enableTotp')->willReturn(true);

        $response->method('withHeader')->willReturn($response);
        $response->method('withStatus')->willReturn($response);

        $controller->doSetup($request, $response);

        // Assert
        $this->assertCount(1, $spy->records);
        $this->assertEquals(TelemetryEventTypeEnum::RESOURCE_MUTATION, $spy->records[0]->eventType);
        $this->assertEquals('2fa_setup', $spy->records[0]->metadata['action']);
    }
}
