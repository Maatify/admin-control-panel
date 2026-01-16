<?php

declare(strict_types=1);

namespace Tests\Http\Controllers;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Application\Telemetry\Contracts\TelemetryEmailHasherInterface;
use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\RequestContext;
use App\Domain\ActivityLog\Action\AdminActivityAction;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Domain\DTO\AdminLoginResultDTO;
use App\Domain\Service\AdminAuthenticationService;
use App\Http\Controllers\AuthController;
use App\Modules\ActivityLog\Contracts\ActivityLogWriterInterface;
use App\Modules\ActivityLog\DTO\ActivityLogDTO;
use App\Modules\ActivityLog\Service\ActivityLogService;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Validation\Contracts\SchemaInterface;
use App\Modules\Validation\Contracts\ValidatorInterface;
use App\Modules\Validation\DTO\ValidationResultDTO;
use App\Modules\Validation\Guard\ValidationGuard;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\TelemetryTestHelper;

final class AuthControllerTest extends TestCase
{
    public function testLoginSuccessLogsActivityWithNewContext(): void
    {
        $authService = $this->createMock(AdminAuthenticationService::class);
        $cryptoService = $this->createMock(AdminIdentifierCryptoServiceInterface::class);

        // crypto blind index stub (avoid return type issues)
        $cryptoService->method('deriveEmailBlindIndex')->willReturn('blind-index');

        // auth success
        $authService->method('login')->willReturn(new AdminLoginResultDTO(123, 'token-xyz'));

        // ---- Validation (real guard with fake validator) ----
        $validator = new class implements ValidatorInterface {
            public function validate(SchemaInterface $schema, array $input): ValidationResultDTO
            {
                return TelemetryTestHelper::makeValidValidationResultDTO($input);
            }
        };

        $validationGuard = new ValidationGuard($validator);

        // ---- Activity logging: real service + spy writer ----
        $writer = new class implements ActivityLogWriterInterface {
            /** @var ActivityLogDTO[] */
            public array $written = [];

            public function write(ActivityLogDTO $activity): void
            {
                $this->written[] = $activity;
            }
        };

        $activityLogService = new ActivityLogService($writer);
        $adminActivityLogService = new AdminActivityLogService($activityLogService);

        // ---- Telemetry deps (AuthController now expects 6 args) ----
        $telemetryEmailHasher = $this->createMock(TelemetryEmailHasherInterface::class);
        $helper = TelemetryTestHelper::makeFactoryWithSpyRecorder($telemetryEmailHasher);
        $telemetryFactory = $helper['factory'];
        $spyRecorder = $helper['recorder'];

        $controller = new AuthController(
            $authService,
            $cryptoService,
            $validationGuard,
            $adminActivityLogService,
            $telemetryFactory,
            $telemetryEmailHasher
        );

        // ---- Request / Response mocks ----
        $request = $this->createMock(ServerRequestInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $request->method('getParsedBody')->willReturn([
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $requestContext = new RequestContext('req-123', '127.0.0.1', 'PHPUnit');
        $request->method('getAttribute')->willReturnMap([
            [RequestContext::class, null, $requestContext],
        ]);

        $response->method('getBody')->willReturn($stream);

        // Fluent methods MUST return ResponseInterface
        $response->method('withHeader')->willReturn($response);
        $response->method('withStatus')->willReturn($response);

        // optional: stream write stub
        $stream->method('write')->willReturn(0);

        // Act
        $returned = $controller->login($request, $response);

        // Assert returned type is correct
        self::assertSame($response, $returned);

        // Assert log written
        self::assertCount(1, $writer->written);

        $dto = $writer->written[0];
        self::assertSame(AdminActivityAction::LOGIN_SUCCESS->toString(), $dto->action);
        self::assertSame('admin', $dto->actorType);
        self::assertSame(123, $dto->actorId);
        self::assertSame('req-123', $dto->requestId);
        self::assertSame('127.0.0.1', $dto->ipAddress);
        self::assertSame('PHPUnit', $dto->userAgent);

        // Assert Telemetry
        self::assertCount(1, $spyRecorder->records);
        $telemetry = $spyRecorder->records[0];
        self::assertEquals(TelemetryEventTypeEnum::AUTH_LOGIN_SUCCESS, $telemetry->eventType);
        self::assertSame(123, $telemetry->actorId);
    }
}
