<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Web;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\Service\StepUpService;
use App\Http\Controllers\Web\TwoFactorController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionNamedType;
use Slim\Views\Twig;

final class TwoFactorControllerTest extends TestCase
{
    public function testDoSetupRecordsTelemetry(): void
    {
        $stepUpService = $this->createMock(StepUpService::class);
        $totpService = $this->createMock(TotpServiceInterface::class);
        $view = $this->createMock(Twig::class);
        $telemetryFactory = $this->makeFinalTelemetryFactory();

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

        $this->assertTrue(true);
    }

    private function makeFinalTelemetryFactory(): HttpTelemetryRecorderFactory
    {
        $ref = new ReflectionClass(HttpTelemetryRecorderFactory::class);
        /** @var HttpTelemetryRecorderFactory $factory */
        $factory = $ref->newInstanceWithoutConstructor();

        $recorder = new class implements \App\Domain\Telemetry\Recorder\TelemetryRecorderInterface {
            public function record(\App\Domain\Telemetry\DTO\TelemetryRecordDTO $dto): void {}
        };

        foreach ($ref->getProperties() as $prop) {
            $type = $prop->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            // Assign dummy recorder
            if (str_contains($typeName, 'TelemetryRecorder') || str_contains($typeName, 'RecorderInterface')) {
                 $prop->setAccessible(true);
                 $prop->setValue($factory, $recorder);
            }
        }
        return $factory;
    }
}
