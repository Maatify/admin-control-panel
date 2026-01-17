<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\ActivityLog\Action\AdminActivityAction;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Domain\DTO\Response\VerificationResponseDTO;
use App\Domain\Exception\IdentifierNotFoundException;
use App\Domain\Service\AdminEmailVerificationService;
use App\Infrastructure\Repository\AdminEmailRepository;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\AdminEmailVerifySchema;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;

readonly class AdminEmailVerificationController
{
    public function __construct(
        private AdminEmailVerificationService $service,
        private AdminEmailRepository $repository,
        private ValidationGuard $validationGuard,
        private AdminActivityLogService $adminActivityLogService,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function verify(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $this->validationGuard->check(new AdminEmailVerifySchema(), $args);

        $adminContext = $request->getAttribute(AdminContext::class);
        if (!$adminContext instanceof AdminContext) {
            throw new \RuntimeException('AdminContext missing');
        }

        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$requestContext instanceof RequestContext) {
            throw new \RuntimeException('RequestContext missing');
        }

        $targetAdminId = (int) $args['id'];

        try {
            // 🔹 Domain operation
            $this->service->verify($targetAdminId, $requestContext);

            $status = $this->repository->getVerificationStatus($targetAdminId);

            // ✅ Activity Log — admin verified another admin email
            $this->adminActivityLogService->log(
                adminContext: $adminContext,
                requestContext: $requestContext,
                action: AdminActivityAction::ADMIN_EMAIL_VERIFIED,
                entityType: 'admin',
                entityId: $targetAdminId,
                metadata: [
                    'verification_status' => $status->value,
                ]
            );

            $dto = new VerificationResponseDTO($targetAdminId, $status);

            $json = json_encode($dto, JSON_THROW_ON_ERROR);
            $response->getBody()->write($json);

            return $response->withHeader('Content-Type', 'application/json');

        } catch (IdentifierNotFoundException $e) {
            throw new HttpNotFoundException($request, $e->getMessage());
        }
    }
}
