<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\ActivityLog\Action\AdminActivityAction;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\Request\CreateAdminEmailRequestDTO;
use App\Domain\DTO\Request\VerifyAdminEmailRequestDTO;
use App\Domain\DTO\Response\ActionResultResponseDTO;
use App\Domain\DTO\Response\AdminEmailResponseDTO;
use App\Domain\Enum\IdentifierType;
use App\Domain\Exception\InvalidIdentifierFormatException;
use App\Domain\Exception\UnauthorizedException;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Infrastructure\Repository\AdminRepository;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\AdminAddEmailSchema;
use App\Modules\Validation\Schemas\AdminGetEmailSchema;
use App\Modules\Validation\Schemas\AdminLookupEmailSchema;
use DateTimeImmutable;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Random\RandomException;
use Slim\Exception\HttpBadRequestException;

class AdminController
{
    public function __construct(
        private AdminRepository $adminRepository,
        private AdminEmailRepository $adminEmailRepository,
        private ValidationGuard $validationGuard,
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private AuthoritativeSecurityAuditWriterInterface $outboxWriter,
        private AdminActivityLogService $adminActivityLogService,
        private PDO $pdo
    ) {
    }

    public function create(Request $request, Response $response): Response
    {
        // Require AdminContext (who is creating the admin)
        $creatorAdminId = $request->getAttribute('admin_id');
        if (!is_int($creatorAdminId)) {
             // Should be handled by Auth Middleware, but for safety
             throw new UnauthorizedException('Authenticated admin required');
        }

        $this->pdo->beginTransaction();
        try {
            $adminId = $this->adminRepository->create();
            $createdAt = $this->adminRepository->getCreatedAt($adminId);

            // Audit Log (Authoritative, Fail-Closed)
            $this->outboxWriter->write(new AuditEventDTO(
                actorAdminId: $creatorAdminId,
                action: 'admin_create',
                targetType: 'admin',
                targetId: $adminId,
                riskLevel: 'HIGH',
                payload: [],
                correlationId: bin2hex(random_bytes(16)),
                occurredAt: new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        // Activity Log (Best Effort)
        $requestContext = $request->getAttribute(RequestContext::class);
        if ($requestContext instanceof RequestContext) {
             $this->adminActivityLogService->log(
                adminContext: new AdminContext($creatorAdminId),
                requestContext: $requestContext,
                action: AdminActivityAction::ADMIN_CREATE,
                entityType: 'admin',
                entityId: $adminId
            );
        }

        $dto = new ActionResultResponseDTO(
            adminId: $adminId,
            createdAt: $createdAt
        );

        $json = json_encode($dto->jsonSerialize(), JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * @param array<string, string> $args
     * @throws RandomException
     * @throws HttpBadRequestException
     */
    public function addEmail(Request $request, Response $response, array $args): Response
    {
        // Require AdminContext
        $actorAdminId = $request->getAttribute('admin_id');
         if (!is_int($actorAdminId)) {
             throw new UnauthorizedException('Authenticated admin required');
        }

        $adminId = (int)$args['id'];
        
        $data = (array)$request->getParsedBody();

        $input = array_merge($data, $args);

        $this->validationGuard->check(new AdminAddEmailSchema(), $input);

        $emailInput = $data[IdentifierType::EMAIL->value] ?? null;

        try {
            $requestDto = new CreateAdminEmailRequestDTO($emailInput);
        } catch (InvalidIdentifierFormatException $e) {
            // Should be caught by validation guard technically, but if schema checks v::email(), it is good.
            // But we keep this just in case.
            throw new HttpBadRequestException($request, 'Invalid email format.');
        }
        $email = $requestDto->email;

        // Blind Index
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($email);

        // Encryption
        $encryptedDto = $this->cryptoService->encryptEmail($email);

        $this->pdo->beginTransaction();
        try {
            $this->adminEmailRepository->addEmail($adminId, $blindIndex, $encryptedDto);

            // Audit Log (Authoritative, Fail-Closed)
            $this->outboxWriter->write(new AuditEventDTO(
                actorAdminId: $actorAdminId,
                action: 'admin_email_add',
                targetType: 'admin',
                targetId: $adminId,
                riskLevel: 'HIGH',
                payload: ['key_id' => $encryptedDto->keyId], // Log non-sensitive metadata
                correlationId: bin2hex(random_bytes(16)),
                occurredAt: new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        // Activity Log (Best Effort)
        $requestContext = $request->getAttribute(RequestContext::class);
        if ($requestContext instanceof RequestContext) {
             $this->adminActivityLogService->log(
                adminContext: new AdminContext($actorAdminId),
                requestContext: $requestContext,
                action: AdminActivityAction::ADMIN_EMAIL_ADD,
                entityType: 'admin',
                entityId: $adminId
            );
        }

        $responseDto = new ActionResultResponseDTO(
            adminId: $adminId,
            emailAdded: true,
        );

        $json = json_encode($responseDto->jsonSerialize(), JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * @throws HttpBadRequestException
     */
    public function lookupEmail(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();

        $this->validationGuard->check(new AdminLookupEmailSchema(), $data);
        
        $emailInput = $data[IdentifierType::EMAIL->value] ?? null;

        try {
            $requestDto = new VerifyAdminEmailRequestDTO($emailInput);
        } catch (InvalidIdentifierFormatException $e) {
             // Redundant with validation but safe
            throw new HttpBadRequestException($request, 'Invalid email format.');
        }
        $email = $requestDto->email;

        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($email);

        $adminId = $this->adminEmailRepository->findByBlindIndex($blindIndex);

        if ($adminId !== null) {
            $responseDto = new ActionResultResponseDTO(
                adminId: $adminId,
                exists: true,
            );
        } else {
            $responseDto = new ActionResultResponseDTO(
                exists: false,
            );
        }

        $json = json_encode($responseDto->jsonSerialize(), JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * @param array<string, string> $args
     */
    public function getEmail(Request $request, Response $response, array $args): Response
    {
        $this->validationGuard->check(new AdminGetEmailSchema(), $args);

        $adminId = (int)$args['id'];

        $encryptedEmailDto = $this->adminEmailRepository->getEncryptedEmail($adminId);

        $email = null;
        if ($encryptedEmailDto !== null) {
            $email = $this->cryptoService->decryptEmail($encryptedEmailDto);
        }

        $responseDto = new AdminEmailResponseDTO($adminId, $email);

        $json = json_encode($responseDto->jsonSerialize(), JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
