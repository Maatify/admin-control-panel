<?php

declare(strict_types=1);

namespace Tests\Http\Controllers;

use App\Application\Services\DiagnosticsTelemetryService;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\DTO\TotpVerificationResultDTO;
use App\Domain\Service\StepUpService;
use App\Http\Controllers\StepUpController;
use App\Modules\Validation\Guard\ValidationGuard;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class StepUpControllerTest extends TestCase
{
    public function testVerifySuccessRecordsTelemetry(): void
    {
        $stepUpService = $this->createMock(StepUpService::class);

        $validator = $this->createMock(\App\Modules\Validation\Contracts\ValidatorInterface::class);
        $validator->method('validate')->willReturn(new \App\Modules\Validation\DTO\ValidationResultDTO(true, []));
        $validationGuard = new ValidationGuard($validator);

        $telemetryService = $this->createMock(DiagnosticsTelemetryService::class);
        $telemetryService->expects($this->once())
            ->method('recordEvent')
            ->with(
                'auth_stepup_success',
                'INFO',
                'ADMIN',
                123
            );

        $controller = new StepUpController(
            $stepUpService,
            $validationGuard,
            $telemetryService
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

        $resultDto = new TotpVerificationResultDTO(true);
        $stepUpService->method('verifyTotp')->willReturn($resultDto);

        $response->method('getBody')->willReturn($stream);
        $response->method('withHeader')->willReturn($response);
        $response->method('withStatus')->willReturn($response);

        // Act
        $controller->verify($request, $response);
    }
}
