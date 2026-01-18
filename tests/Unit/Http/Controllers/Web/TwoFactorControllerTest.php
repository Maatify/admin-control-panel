<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers\Web;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\DTO\TotpVerificationResultDTO;
use App\Domain\Service\StepUpService;
use App\Domain\Telemetry\Recorder\TelemetryRecorderInterface;
use App\Http\Controllers\Web\TwoFactorController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Slim\Views\Twig;

class TwoFactorControllerTest extends TestCase
{
    private MockObject|StepUpService $stepUpService;
    private MockObject|TotpServiceInterface $totpService;
    private MockObject|Twig $view;
    private HttpTelemetryRecorderFactory $telemetryFactory; // Real
    private MockObject|TelemetryRecorderInterface $telemetryRecorder; // Mock
    private TwoFactorController $controller;

    protected function setUp(): void
    {
        $this->stepUpService = $this->createMock(StepUpService::class);
        $this->totpService = $this->createMock(TotpServiceInterface::class);
        $this->view = $this->createMock(Twig::class);

        $this->telemetryRecorder = $this->createMock(TelemetryRecorderInterface::class);
        $this->telemetryFactory = new HttpTelemetryRecorderFactory($this->telemetryRecorder);

        $this->controller = new TwoFactorController(
            $this->stepUpService,
            $this->totpService,
            $this->view,
            $this->telemetryFactory
        );
    }

    private function createMockResponse(): MockObject|ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        return $response;
    }

    public function testSetupRendersView(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMockResponse();

        $this->totpService->method('generateSecret')->willReturn('new-secret');

        $this->view->expects($this->once())
            ->method('render')
            ->with($response, '2fa-setup.twig', ['secret' => 'new-secret'])
            ->willReturn($response);

        $this->controller->setup($request, $response);
    }

    public function testDoSetupUnauthorized(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMockResponse();

        $request->method('getParsedBody')->willReturn(['secret' => 's', 'code' => 'c']);
        $request->method('getAttribute')->with(AdminContext::class)->willReturn(null);

        $stream = $this->createMock(StreamInterface::class);
        $response->method('getBody')->willReturn($stream);
        $response->expects($this->once())->method('withStatus')->with(401)->willReturn($response);

        $this->controller->doSetup($request, $response);
    }

    public function testDoSetupSuccess(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMockResponse();
        $context = new RequestContext('req1', 'ip', 'ua');
        $adminContext = new AdminContext(1);

        $request->method('getParsedBody')->willReturn(['secret' => 's', 'code' => '123456']);
        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $context],
        ]);
        $request->method('getCookieParams')->willReturn(['auth_token' => 'session123']);

        $this->stepUpService->method('enableTotp')
            ->with(1, 'session123', 's', '123456', $context)
            ->willReturn(true);

        // Expect record on the inner recorder
        $this->telemetryRecorder->expects($this->once())->method('record');

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', '/dashboard')
            ->willReturn($response);
        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturn($response);

        $this->controller->doSetup($request, $response);
    }

    public function testDoSetupFailure(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMockResponse();
        $context = new RequestContext('req1', 'ip', 'ua');
        $adminContext = new AdminContext(1);

        $request->method('getParsedBody')->willReturn(['secret' => 's', 'code' => 'wrong']);
        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $context],
        ]);
        $request->method('getCookieParams')->willReturn(['auth_token' => 'session123']);

        $this->stepUpService->method('enableTotp')->willReturn(false);

        $this->view->expects($this->once())
            ->method('render')
            ->with($response, '2fa-setup.twig', ['error' => 'Invalid code', 'secret' => 's'])
            ->willReturn($response);

        $this->controller->doSetup($request, $response);
    }

    public function testVerifyRendersView(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMockResponse();

        $this->view->expects($this->once())
            ->method('render')
            ->with($response, '2fa-verify.twig')
            ->willReturn($response);

        $this->controller->verify($request, $response);
    }

    public function testDoVerifySuccess(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMockResponse();
        $context = new RequestContext('req1', 'ip', 'ua');
        $adminContext = new AdminContext(1);

        $request->method('getParsedBody')->willReturn(['code' => '123456']);
        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $context],
        ]);
        $request->method('getCookieParams')->willReturn(['auth_token' => 'session123']);

        $result = new TotpVerificationResultDTO(true);
        $this->stepUpService->method('verifyTotp')->willReturn($result);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', '/dashboard')
            ->willReturn($response);
        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturn($response);

        $this->controller->doVerify($request, $response);
    }

    public function testDoVerifyFailure(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMockResponse();
        $context = new RequestContext('req1', 'ip', 'ua');
        $adminContext = new AdminContext(1);

        $request->method('getParsedBody')->willReturn(['code' => 'wrong']);
        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $context],
        ]);
        $request->method('getCookieParams')->willReturn(['auth_token' => 'session123']);

        $result = new TotpVerificationResultDTO(false, 'Bad code');
        $this->stepUpService->method('verifyTotp')->willReturn($result);

        $this->view->expects($this->once())
            ->method('render')
            ->with($response, '2fa-verify.twig', ['error' => 'Bad code'])
            ->willReturn($response);

        $this->controller->doVerify($request, $response);
    }

    public function testDoVerifyTelemetrySwallowsException(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMockResponse();
        $context = new RequestContext('req1', 'ip', 'ua');
        $adminContext = new AdminContext(1);

        $request->method('getParsedBody')->willReturn(['code' => '123456']);
        $request->method('getAttribute')->willReturnMap([
            [AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $context],
        ]);
        $request->method('getCookieParams')->willReturn(['auth_token' => 'session123']);

        $this->stepUpService->method('verifyTotp')->willReturn(new TotpVerificationResultDTO(true));

        // Mock telemetry recorder to throw exception
        $this->telemetryRecorder->method('record')->willThrowException(new \Exception("Telemetry fail"));

        // Should still succeed
        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', '/dashboard')
            ->willReturn($response);

        $this->controller->doVerify($request, $response);
    }
}
