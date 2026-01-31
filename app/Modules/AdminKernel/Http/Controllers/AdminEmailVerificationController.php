<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Admin\Enum\AdminActivityActionEnum;
use Maatify\AdminKernel\Domain\DTO\Response\VerificationResponseDTO;
use Maatify\AdminKernel\Domain\Exception\IdentifierNotFoundException;
use Maatify\AdminKernel\Domain\Service\AdminEmailVerificationService;
use Maatify\AdminKernel\Infrastructure\Repository\AdminEmailRepository;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\AdminEmailVerifySchema;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;

readonly class AdminEmailVerificationController
{
    public function __construct(
        private AdminEmailVerificationService $service,
        private AdminEmailRepository $repository,
        private ValidationGuard $validationGuard,
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
            AdminActivityActionEnum::ADMIN_EMAIL_VERIFIED
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
            AdminActivityActionEnum::ADMIN_EMAIL_FAILED
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
            AdminActivityActionEnum::ADMIN_EMAIL_REPLACED
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
            AdminActivityActionEnum::ADMIN_EMAIL_VERIFICATION_RESTARTED
        );
    }

    /* ===============================
     * INTERNAL HANDLER
     * ===============================
     * */
    /**
     * @param   ServerRequestInterface          $request
     * @param   ResponseInterface               $response
     * @param   array<string, string>           $args
     * @param   callable                        $action
     * @param   AdminActivityActionEnum|string  $activityAction  Activity action identifier
     *                                                       (values defined in AdminActivityActionEnum constants)
     *
     * @return ResponseInterface
     * @throws \JsonException
     */
    private function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
        callable $action,
        AdminActivityActionEnum|string $activityAction
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
