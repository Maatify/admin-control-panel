<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Web;

use App\Application\Services\DiagnosticsTelemetryService;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\Service\StepUpService;
use App\Http\Controllers\Web\TwoFactorController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

final class TwoFactorControllerTest extends TestCase
{
    public function testDoSetupRecordsTelemetry(): void
    {
        $stepUpService = $this->createMock(StepUpService::class);
        $totpService = $this->createMock(TotpServiceInterface::class);
        $view = $this->createMock(Twig::class);
        $telemetryService = $this->createMock(DiagnosticsTelemetryService::class);

        $telemetryService->expects($this->once())
            ->method('recordEvent')
            ->with(
                'resource_mutation',
                'INFO',
                'ADMIN',
                123,
                $this->callback(function($metadata) {
                    return isset($metadata['action']) && $metadata['action'] === '2fa_setup';
                })
            );

        $controller = new TwoFactorController(
            $stepUpService,
            $totpService,
            $view,
            $telemetryService
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
    }
}
