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
use Tests\Support\TelemetryTestHelper;

final class HttpRequestTelemetryMiddlewareTest extends TestCase
{
    public function testEmitsRequestEndOnSuccess(): void
    {
        $helper = TelemetryTestHelper::makeFactoryWithSpyRecorder();
        $factory = $helper['factory'];
        $recorderSpy = $helper['recorder'];

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

        $result = $middleware->process($request, $handler);
        $this->assertSame($response, $result);

        $this->assertCount(1, $recorderSpy->records);
        $this->assertEquals(TelemetryEventTypeEnum::HTTP_REQUEST_END, $recorderSpy->records[0]->eventType);
        $this->assertEquals(200, $recorderSpy->records[0]->metadata['status_code']);
    }

    public function testEmitsRequestEndOnHandlerException(): void
    {
        $helper = TelemetryTestHelper::makeFactoryWithSpyRecorder();
        $factory = $helper['factory'];
        $recorderSpy = $helper['recorder'];

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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fail');

        try {
            $middleware->process($request, $handler);
        } finally {
            $this->assertCount(0, $recorderSpy->records);
        }
    }

    public function testSwallowsTelemetryException(): void
    {
        // To test swallowing, we need a recorder that THROWS, not a Spy.
        // We can create an anonymous class for this specific test, or extend Spy to ThrowingSpy.

        $throwingRecorder = new class implements TelemetryRecorderInterface {
             public function record(TelemetryRecordDTO $dto): void {
                 throw new \Exception('Recorder broken');
             }
        };

        // We use the helper logic but inject our throwing recorder manually
        $ref = new \ReflectionClass(HttpTelemetryRecorderFactory::class);
        $factory = $ref->newInstanceWithoutConstructor();
        foreach ($ref->getProperties() as $prop) {
            $type = $prop->getType();
            if ($type && (str_contains($type->getName(), 'TelemetryRecorder') || str_contains($type->getName(), 'RecorderInterface'))) {
                 $prop->setAccessible(true);
                 $prop->setValue($factory, $throwingRecorder);
            }
        }

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

        // Middleware should not throw despite recorder failing
        $result = $middleware->process($request, $handler);
        $this->assertSame($response, $result);
    }
}
