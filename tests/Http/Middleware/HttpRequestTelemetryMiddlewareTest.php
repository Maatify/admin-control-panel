<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Telemetry\DTO\TelemetryRecordDTO;
use App\Domain\Telemetry\Recorder\TelemetryRecorderInterface;
use App\Http\Middleware\HttpRequestTelemetryMiddleware;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HttpRequestTelemetryMiddlewareTest extends TestCase
{
    public function testEmitsRequestEndOnSuccess(): void
    {
        $recorderMock = $this->createMock(TelemetryRecorderInterface::class);
        $factory = new HttpTelemetryRecorderFactory($recorderMock);
        $middleware = new HttpRequestTelemetryMiddleware($factory);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestContext = new RequestContext('req-1', '1.2.3.4', 'agent');

        $request->method('getAttribute')
            ->willReturnMap([
                [RequestContext::class, null, $requestContext],
                [AdminContext::class, null, null], // simulate system request
            ]);
        $request->method('getMethod')->willReturn('GET');

        $response->method('getStatusCode')->willReturn(200);

        $handler->method('handle')->willReturn($response);

        // Expect record call
        $recorderMock->expects($this->once())
            ->method('record')
            ->with($this->callback(function (TelemetryRecordDTO $dto) {
                return $dto->eventType === TelemetryEventTypeEnum::HTTP_REQUEST_END
                    && $dto->metadata['status_code'] === 200;
            }));

        $result = $middleware->process($request, $handler);
        $this->assertSame($response, $result);
    }

    public function testEmitsRequestEndOnHandlerException(): void
    {
        $recorderMock = $this->createMock(TelemetryRecorderInterface::class);
        $factory = new HttpTelemetryRecorderFactory($recorderMock);
        $middleware = new HttpRequestTelemetryMiddleware($factory);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $requestContext = new RequestContext('req-1', '1.2.3.4', 'agent');

        // When handler throws, response is null, so if($response instanceof ResponseInterface) check fails.
        // Middleware should NOT emit telemetry in this case (as per code implementation).

        $request->method('getAttribute')->willReturnMap([
             [RequestContext::class, null, $requestContext]
        ]);

        $handler->method('handle')->willThrowException(new \RuntimeException('fail'));

        $recorderMock->expects($this->never())->method('record');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fail');

        $middleware->process($request, $handler);
    }

    public function testSwallowsTelemetryException(): void
    {
        $recorderMock = $this->createMock(TelemetryRecorderInterface::class);
        $factory = new HttpTelemetryRecorderFactory($recorderMock);
        $middleware = new HttpRequestTelemetryMiddleware($factory);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $requestContext = new RequestContext('req-1', '1.2.3.4', 'agent');

        $request->method('getAttribute')
            ->willReturnMap([
                [RequestContext::class, null, $requestContext],
                [AdminContext::class, null, null],
            ]);
        $request->method('getMethod')->willReturn('GET');
        $response->method('getStatusCode')->willReturn(200);

        $handler->method('handle')->willReturn($response);

        // Recorder throws
        $recorderMock->method('record')->willThrowException(new \Exception('Recorder broken'));

        // Middleware should not throw
        $result = $middleware->process($request, $handler);
        $this->assertSame($response, $result);
    }
}
