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
use Random\RandomException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

readonly class AdminEmailVerificationController
{
    public function __construct(
        private AdminEmailVerificationService $service,
        private AdminEmailRepository $repository,
        private ValidationGuard $validationGuard,
        private AdminActivityLogService $activityLog,
    ) {}

    /* ===============================
     * VERIFY (Admin action)
     * =============================== */
    /**
     * @param   array<string, string>  $args
     *
     * @throws \JsonException
     */
    public function verify(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        return $this->handle(
            $request,
            $response,
            $args,
            fn(int $emailId, RequestContext $ctx) => $this->service->verify($emailId, $ctx),
            AdminActivityAction::ADMIN_EMAIL_VERIFIED
        );
    }

    /* ===============================
     * FAIL
     * =============================== */
    /**
     * @param   array<string, string>  $args
     *
     * @throws \JsonException
     */
    public function fail(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        return $this->handle(
            $request,
            $response,
            $args,
            fn(int $emailId, RequestContext $ctx) => $this->service->fail($emailId, $ctx),
            AdminActivityAction::ADMIN_EMAIL_FAILED
        );
    }

    /* ===============================
     * REPLACE
     * =============================== */
    /**
     * @param   array<string, string>  $args
     *
     * @throws \JsonException
     */
    public function replace(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        return $this->handle(
            $request,
            $response,
            $args,
            fn(int $emailId, RequestContext $ctx) => $this->service->replace($emailId, $ctx),
            AdminActivityAction::ADMIN_EMAIL_REPLACED
        );
    }

    /* ===============================
     * RESTART VERIFICATION
     * =============================== */
    /**
     * @param   array<string, string>  $args
     *
     * @throws \JsonException
     */
    public function restart(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        return $this->handle(
            $request,
            $response,
            $args,
            fn(int $emailId, RequestContext $ctx) => $this->service->restart($emailId, $ctx),
            AdminActivityAction::ADMIN_EMAIL_VERIFICATION_RESTARTED
        );
    }

    /* ===============================
     * INTERNAL HANDLER
     * ===============================
     * */
    /**
     * @param   ServerRequestInterface      $request
     * @param   ResponseInterface           $response
     * @param   array<string, string>       $args
     * @param   callable                    $action
     * @param   AdminActivityAction|string  $activityAction  Activity action identifier
     *                                                       (values defined in AdminActivityAction constants)
     *
     * @return ResponseInterface
     * @throws \JsonException
     */
    private function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
        callable $action,
        AdminActivityAction|string $activityAction
    ): ResponseInterface {
        $emailId = (int) $args['emailId'];

        $data = (array)$request->getParsedBody();

        $input = array_merge($data, $args);

        $this->validationGuard->check(new AdminEmailVerifySchema(), $input);

        $adminContext = $request->getAttribute(AdminContext::class);
        if (!$adminContext instanceof AdminContext) {
            throw new \RuntimeException('AdminContext missing');
        }

        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$requestContext instanceof RequestContext) {
            throw new \RuntimeException('RequestContext missing');
        }


        try {
            // 🔹 Domain action
            $action($emailId, $requestContext);

            // 🔹 Reload identity
            $identity = $this->repository->getEmailIdentity($emailId);

            // 🔹 Activity log
            $this->activityLog->log(
                adminContext: $adminContext,
                requestContext: $requestContext,
                action: $activityAction,
                entityType: 'admin',
                entityId: $identity->adminId,
                metadata: [
                    'email_id' => $identity->emailId,
                    'status'   => $identity->verificationStatus->value,
                ]
            );

            $dto = new VerificationResponseDTO(
                adminId: $identity->adminId,
                emailId: $identity->emailId,
                status: $identity->verificationStatus
            );

            $json = json_encode($dto, JSON_THROW_ON_ERROR);
            $response->getBody()->write($json);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        } catch (IdentifierNotFoundException $e) {
            throw new HttpNotFoundException($request, $e->getMessage());
        }
    }
}
