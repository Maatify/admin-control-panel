<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Application\Telemetry\Contracts\TelemetryEmailHasherInterface;
use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\ActivityLog\Action\AdminActivityAction;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Domain\DTO\LoginRequestDTO;
use App\Domain\DTO\LoginResponseDTO;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Service\AdminAuthenticationService;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\AuthLoginSchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class AuthController
{
    public function __construct(
        private AdminAuthenticationService $authService,
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private ValidationGuard $validationGuard,
        private AdminActivityLogService $adminActivityLogService,

        // Telemetry (best-effort)
        private HttpTelemetryRecorderFactory $telemetryFactory,
        private TelemetryEmailHasherInterface $telemetryEmailHasher,
    ) {}

    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $this->validationGuard->check(new AuthLoginSchema(), $data);

        $dto = new LoginRequestDTO(
            (string) $data['email'],
            (string) $data['password']
        );

        // Blind Index Calculation (Auth lookup)
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($dto->email);

        try {
            $requestContext = $request->getAttribute(RequestContext::class);
            if (! $requestContext instanceof RequestContext) {
                // Middleware should hard-fail earlier, but keep type safety.
                throw new \RuntimeException('Request Context not present');
            }

            $result = $this->authService->login($blindIndex, $dto->password, $requestContext);

            // 🔹 Construct Contexts (Canonical)
            $adminContext = new AdminContext($result->adminId);

            // Activity Log (authoritative operational record)
            $this->adminActivityLogService->log(
                adminContext: $adminContext,
                requestContext: $requestContext,
                action: AdminActivityAction::LOGIN_SUCCESS,
                metadata: [
                    'method' => 'password',
                ]
            );

            // Telemetry (best-effort)
            try {
                $this->telemetryFactory
                    ->admin($requestContext)
                    ->record(
                        actorId: $result->adminId,
                        eventType: TelemetryEventTypeEnum::AUTH_LOGIN_SUCCESS,
                        severity: TelemetrySeverityEnum::INFO,
                        metadata: [
                            'result' => 'success',
                        ]
                    );
            } catch (\Throwable) {
                // swallow
            }

            $responseDto = new LoginResponseDTO($result->token);
            $response->getBody()->write((string) json_encode($responseDto));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidCredentialsException $e) {
            // Telemetry (system) — best-effort, with email_hash only (no PII)
            try {
                $requestContext = $request->getAttribute(RequestContext::class);
                if ($requestContext instanceof RequestContext) {
                    $meta = [
                        'result' => 'failure',
                        'error_reason' => 'invalid_credentials',
                    ];

                    $hashDTO = $this->telemetryEmailHasher->hashEmail($dto->email);
                    if ($hashDTO !== null) {
                        $meta['email_hash'] = $hashDTO->hash;
                        $meta['email_hash_key_id'] = $hashDTO->keyId;
                        $meta['email_hash_algo'] = $hashDTO->algo;
                    }

                    $this->telemetryFactory
                        ->system($requestContext)
                        ->record(
                            eventType: TelemetryEventTypeEnum::AUTH_LOGIN_FAILURE,
                            severity: TelemetrySeverityEnum::WARN,
                            metadata: $meta
                        );
                }
            } catch (\Throwable) {
                // swallow
            }

            // ❌ No admin context here (unknown identity)
            $response->getBody()->write((string) json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        } catch (AuthStateException $e) {
            // Telemetry (system) — best-effort, with email_hash only
            try {
                $requestContext = $request->getAttribute(RequestContext::class);
                if ($requestContext instanceof RequestContext) {
                    $meta = [
                        'result' => 'failure',
                        'error_reason' => 'auth_state',
                    ];

                    $hashDTO = $this->telemetryEmailHasher->hashEmail($dto->email);
                    if ($hashDTO !== null) {
                        $meta['email_hash'] = $hashDTO->hash;
                        $meta['email_hash_key_id'] = $hashDTO->keyId;
                        $meta['email_hash_algo'] = $hashDTO->algo;
                    }

                    $this->telemetryFactory
                        ->system($requestContext)
                        ->record(
                            eventType: TelemetryEventTypeEnum::AUTH_LOGIN_FAILURE,
                            severity: TelemetrySeverityEnum::WARN,
                            metadata: $meta
                        );
                }
            } catch (\Throwable) {
                // swallow
            }

            $response->getBody()->write((string) json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
    }
}
